<?php

namespace LWVendor\Orhanerday\OpenAi;

use Exception;
class OpenAi
{
    private $engine = "davinci";
    private $model = "text-davinci-002";
    private $chatModel = "gpt-3.5-turbo";
    private $headers;
    private $return_headers = [];
    private $contentTypes;
    private $timeout = 90;
    private $concurrency = 100;
    private $stream_method;
    private $customUrl = "";
    private $proxy = "";
    private $curlInfo = [];
    public function __construct($OPENAI_API_KEY)
    {
        $this->contentTypes = ["application/json" => "Content-Type: application/json", "multipart/form-data" => "Content-Type: multipart/form-data"];
        $this->headers = [$this->contentTypes["application/json"], "Authorization: Bearer {$OPENAI_API_KEY}", "Expect:"];
    }
    /**
     * @return array
     * Remove this method from your code before deploying
     */
    public function getCURLInfo()
    {
        return $this->curlInfo;
    }
    /**
     * @return array
     */
    public function getResponseHeaders(){
        return $this->return_headers;
    }
    /**
     * @return bool|string
     */
    public function listModels()
    {
        $url = Url::fineTuneModel();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $model
     * @return bool|string
     */
    public function retrieveModel($model)
    {
        $model = "/{$model}";
        $url = Url::fineTuneModel() . $model;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $model
     * @return bool|string
     */
    public function getUsage()
    {
        $url   = Url::usageURL();
        $this->baseUrl($url);
        $start_date = '2024-05-11';
        $end_date = '2024-05-30';

        //$url .= "?date={$start_date}";
        $url .= "?start_date={$start_date}&end_date={$end_date}";

        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function complete($opts)
    {
        $engine = isset($opts['engine']) ? $opts['engine']: $this->engine;
        $url    = Url::completionURL($engine);
        unset($opts['engine']);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param        $opts
     * @param  null  $stream
     * @return bool|string
     * @throws Exception
     */
    public function completion($opts, $stream = null)
    {
        if (\array_key_exists('stream', $opts) && $opts['stream']) {
            if ($stream == null) {
                throw new Exception('Please provide a stream function. Check https://github.com/orhanerday/open-ai#stream-example for an example.');
            }
            $this->stream_method = $stream;
        }
        $opts['model'] = isset($opts['model']) ? $opts['model']: $this->model;
        $url           = Url::completionsURL();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function createEdit($opts)
    {
        $url = Url::editsUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function image($opts)
    {
        $url = Url::imageUrl() . "/generations";
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function imageEdit($opts)
    {
        $url = Url::imageUrl() . "/edits";
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function createImageVariation($opts)
    {
        $url = Url::imageUrl() . "/variations";
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function search($opts)
    {
        $engine = isset($opts['engine']) ? $opts['engine']: $this->engine;
        $url    = Url::searchURL($engine);
        unset($opts['engine']);
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function answer($opts)
    {
        $url = Url::answersUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     * @deprecated
     */
    public function classification($opts)
    {
        $url = Url::classificationsUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function moderation($opts)
    {
        $url = Url::moderationUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param        $opts
     * @param  null  $stream
     * @return bool|string|array
     * @throws Exception
     */
    public function chat($opts, $stream = null, $multi = false)
    {
        if ($stream != null && \array_key_exists('stream', $opts)) {
            if (!$opts['stream']) {
                throw new Exception('Please provide a stream function. Check https://github.com/orhanerday/open-ai#stream-example for an example.');
            }
            $this->stream_method = $stream;
        }
        $opts['model'] = isset($opts['model']) ? $opts['model']: $this->chatModel;
        $url           = Url::chatUrl();
        $this->baseUrl($url);
        return ($multi) ? $this->sendMultiRequest($url, 'POST', $opts): $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function transcribe($opts)
    {
        $url = Url::transcriptionsUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function translate($opts)
    {
        $url = Url::translationsUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function uploadFile($opts)
    {
        $url = Url::filesUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @return bool|string
     */
    public function listFiles()
    {
        $url = Url::filesUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $file_id
     * @return bool|string
     */
    public function retrieveFile($file_id)
    {
        $file_id = "/{$file_id}";
        $url     = Url::filesUrl().$file_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $file_id
     * @return bool|string
     */
    public function retrieveFileContent($file_id)
    {
        $file_id = "/{$file_id}/content";
        $url     = Url::filesUrl().$file_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $file_id
     * @return bool|string
     */
    public function deleteFile($file_id)
    {
        $file_id = "/{$file_id}";
        $url     = Url::filesUrl().$file_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'DELETE');
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function createFineTune($opts)
    {
        $url = Url::fineTuneUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @return bool|string
     */
    public function listFineTunes()
    {
        $url = Url::fineTuneUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function retrieveFineTune($fine_tune_id)
    {
        $fine_tune_id = "/{$fine_tune_id}";
        $url          = Url::fineTuneUrl().$fine_tune_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function cancelFineTune($fine_tune_id)
    {
        $fine_tune_id = "/{$fine_tune_id}/cancel";
        $url          = Url::fineTuneUrl().$fine_tune_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST');
    }
    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function listFineTuneEvents($fine_tune_id)
    {
        $fine_tune_id = "/{$fine_tune_id}/events";
        $url          = Url::fineTuneUrl().$fine_tune_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $fine_tune_id
     * @return bool|string
     */
    public function deleteFineTune($fine_tune_id)
    {
        $fine_tune_id = "/{$fine_tune_id}";
        $url          = Url::fineTuneModel().$fine_tune_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'DELETE');
    }
    /**
     * @param $opts
     * @return bool|string
     */
    public function createBatch($opts)
    {
        $url = Url::batchesUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param $batch_id
     * @return bool|string
     */
    public function retrieveBatch($batch_id)
    {
        $batch_id = "/{$batch_id}";
        $url = Url::batchesUrl().$batch_id;
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $batch_id
     * @return bool|string
     */
    public function listBatches($limit = 0)
    {
        $url = Url::batchesUrl();
        if(!empty($limit)){
            $url.'?limit='.intval($limit);
        }
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param
     * @return bool|string
     * @deprecated
     */
    public function engines()
    {
        $url = Url::enginesUrl();
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $engine
     * @return bool|string
     * @deprecated
     */
    public function engine($engine)
    {
        $url = Url::engineUrl($engine);
        $this->baseUrl($url);
        return $this->sendRequest($url, 'GET');
    }
    /**
     * @param $opts
     * @param $multi
     * @return bool|string|array
     */
    public function embeddings($opts, $multi = false)
    {
        $url = Url::embeddings();
        $this->baseUrl($url);
        return ($multi) ? $this->sendMultiRequest($url, 'POST', $opts): $this->sendRequest($url, 'POST', $opts);
    }
    /**
     * @param  int  $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
    }
    /**
     * @param  int  $timeout
     */
    public function setConcurrency($concurrency)
    {
        $this->concurrency = (int)$concurrency;
    }
    /**
     * @param  string  $proxy
     */
    public function setProxy(string $proxy)
    {
        if ($proxy && \strpos($proxy, '://') === \false) {
            $proxy = 'https://' . $proxy;
        }
        $this->proxy = $proxy;
    }
    /**
     * @param  string  $customUrl
     * @deprecated
     */
    /**
     * @param  string  $customUrl
     * @return void
     */
    public function setCustomURL($customUrl)
    {
        if ($customUrl != "") {
            $this->customUrl = (string)$customUrl;
        }
    }
    /**
     * @param  string  $customUrl
     * @return void
     */
    public function setBaseURL($customUrl)
    {
        if ($customUrl != '') {
            $this->customUrl = (string)$customUrl;
        }
    }
    /**
     * @param  array  $header
     * @return void
     */
    public function setHeader($header)
    {
        $header = (array)$header;
        if ($header) {
            foreach ($header as $key => $value) {
                $this->headers[$key] = $value;
            }
        }
    }
    /**
     * @param  string  $org
     */
    public function setORG($org)
    {
        $org = (string)$org;
        if ($org != "") {
            $this->headers[] = "OpenAI-Organization: {$org}";
        }
    }
    /**
     * @param  string  $url
     * @param  string  $method
     * @param  array   $opts
     * @return bool|string
     */
    private function sendRequest($url, $method, $opts = [])
    {
        $post_fields = wp_json_encode($opts);
        if (\array_key_exists('file', $opts) || \array_key_exists('image', $opts)) {
            $this->headers[0] = $this->contentTypes["multipart/form-data"];
            $post_fields      = $opts;
        } else {
            $this->headers[0] = $this->contentTypes["application/json"];
        }
        $curl_info = [
            \CURLOPT_USERAGENT      => WPIL_DATA_USER_AGENT,
            \CURLOPT_URL            => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_ENCODING       => '',
            \CURLOPT_MAXREDIRS      => 10,
            \CURLOPT_TIMEOUT        => $this->timeout,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            \CURLOPT_CUSTOMREQUEST  => $method,
            \CURLOPT_POSTFIELDS     => $post_fields,
            \CURLOPT_HTTPHEADER     => $this->headers,
            \CURLOPT_HEADERFUNCTION => function($curl, $header){
                $this->return_headers[] = $header;
                return strlen($header);
            }
        ];

        if ($opts == []) {
            unset($curl_info[\CURLOPT_POSTFIELDS]);
        }
        if (!empty($this->proxy)) {
            $curl_info[\CURLOPT_PROXY] = $this->proxy;
        }
        if (\array_key_exists('stream', $opts) && $opts['stream']) {
            $curl_info[\CURLOPT_WRITEFUNCTION] = $this->stream_method;
        }
        $curl = \curl_init();
        \curl_setopt_array($curl, $curl_info);
        $response = \curl_exec($curl);
        $info = \curl_getinfo($curl);
        $this->curlInfo = $info;
        \curl_close($curl);

        return $response;
    }

    /**
     * @param  string  $url
     * @param  string  $method
     * @param  array   $opts
     * @return bool|string
     */
    private function sendMultiRequest($url, $method, $opts = [])
    {
        // create the multihandle
        $mh = \curl_multi_init();
        $handles = array();
        $messages = $opts['message_list'];
        unset($opts['message_list']);

        for($i = 0; $i < $this->concurrency; $i++){
            if(!isset($messages[$i])){
                break;
            }

            $curl_opts = \array_merge($opts, $messages[$i]);

            if (\array_key_exists('file', $opts) || \array_key_exists('image', $opts)) {
                $this->headers[0] = $this->contentTypes["multipart/form-data"];
                $post_fields      = $curl_opts;
            } else {
                $post_fields    = wp_json_encode($curl_opts);
                $this->headers[0] = $this->contentTypes["application/json"];
            }
            $curl_info = [
                \CURLOPT_URL            => $url,
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_ENCODING       => '',
                \CURLOPT_MAXREDIRS      => 10,
                \CURLOPT_TIMEOUT        => $this->timeout,
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                \CURLOPT_CUSTOMREQUEST  => $method,
                \CURLOPT_POSTFIELDS     => $post_fields,
                \CURLOPT_HTTPHEADER     => $this->headers,
                \CURLOPT_BUFFERSIZE     => 65536,
                \CURLOPT_HEADERFUNCTION => function($curl, $header) use ($i){
                    // Initialize the array if it's not set
                    if(!isset($this->return_headers[$i]) || !is_array($this->return_headers[$i])){
                        $this->return_headers[$i] = [];
                    }

                    $this->return_headers[$i][] = $header;
                    return strlen($header);
                }
            ];
            if ($curl_opts == []) {
                unset($curl_info[\CURLOPT_POSTFIELDS]);
            }
            if (!empty($this->proxy)) {
                $curl_info[\CURLOPT_PROXY] = $this->proxy;
            }
            if (\array_key_exists('stream', $opts) && $opts['stream']) {
                $curl_info[\CURLOPT_WRITEFUNCTION] = $this->stream_method;
            }
    
            $handles[$i] = \curl_init();
            \curl_setopt_array($handles[$i], $curl_info);
            \curl_multi_add_handle($mh, $handles[$i]);
        }

        if(!empty($handles)){
            do {
                $status = \curl_multi_exec($mh, $active);
                
                // Check if any handle has completed
                while ($info = curl_multi_info_read($mh)) {
                    $handle = $info['handle'];

                    // Only process if the handle completed successfully
                    if ($info['result'] === CURLE_OK) {
                        // Find the array key associated with this handle
                        $handle_id = \array_search($handle, $handles, true);

                        // Get content from the handle
                        $content = \curl_multi_getcontent($handle);
                        $info    = \curl_getinfo($handle);
                        $processed = apply_filters('orhanerday_openai_stream_response_data', $handle_id, $content, $info);
                        if($processed){
                            // Remove the handle once processed
                            \curl_multi_remove_handle($mh, $handle);
                            \curl_close($handle);
                            unset($handles[$handle_id]);
                        }
                    }
                }
                
                if ($active) {
                    \curl_multi_select($mh);
                }
            } while ($active && $status == CURLM_OK);
        }

        $responses = array();
        foreach($handles as $handle_id => $handle){
            $responses[$handle_id] = \curl_multi_getcontent($handle);
            $info           = \curl_getinfo($handle);
            $this->curlInfo[$handle_id] = $info;
            \curl_multi_remove_handle($mh, $handle);
            \curl_close($handle);
        }
        \curl_multi_close($mh);

        return $responses;
    }
    /**
     * @param  string  $url
     */
    private function baseUrl(&$url)
    {
        if ($this->customUrl != "") {
            $url = \str_replace(Url::ORIGIN, $this->customUrl, $url);
        }
    }
}
