<?php

namespace LWVendor\PhpOffice\PhpSpreadsheet\Writer\Xls;

use LWVendor\PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher as SharedEscher;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer\SpContainer;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;
use LWVendor\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE\Blip;
class Escher
{
    /**
     * The object we are writing.
     *
     * @var Blip|BSE|BstoreContainer|DgContainer|DggContainer|Escher|SpContainer|SpgrContainer
     */
    private $object;
    /**
     * The written binary data.
     *
     * @var string
     */
    private $data;
    /**
     * Shape offsets. Positions in binary stream where a new shape record begins.
     *
     * @var array
     */
    private $spOffsets;
    /**
     * Shape types.
     *
     * @var array
     */
    private $spTypes;
    /**
     * Constructor.
     *
     * @param mixed $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }
    /**
     * Process the object to be written.
     *
     * @return string
     */
    public function close()
    {
        // initialize
        $this->data = '';
        switch (\get_class($this->object)) {
            case SharedEscher::class:
                if ($dggContainer = $this->object->getDggContainer()) {
                    $writer = new self($dggContainer);
                    $this->data = $writer->close();
                } elseif ($dgContainer = $this->object->getDgContainer()) {
                    $writer = new self($dgContainer);
                    $this->data = $writer->close();
                    $this->spOffsets = $writer->getSpOffsets();
                    $this->spTypes = $writer->getSpTypes();
                }
                break;
            case DggContainer::class:
                // this is a container record
                // initialize
                $innerData = '';
                // write the dgg
                $recVer = 0x0;
                $recInstance = 0x0;
                $recType = 0xf006;
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                // dgg data
                $dggData = \pack(
                    'VVVV',
                    $this->object->getSpIdMax(),
                    // maximum shape identifier increased by one
                    $this->object->getCDgSaved() + 1,
                    // number of file identifier clusters increased by one
                    $this->object->getCSpSaved(),
                    $this->object->getCDgSaved()
                );
                // add file identifier clusters (one per drawing)
                /** @scrutinizer ignore-call */
                $IDCLs = $this->object->getIDCLs();
                foreach ($IDCLs as $dgId => $maxReducedSpId) {
                    $dggData .= \pack('VV', $dgId, $maxReducedSpId + 1);
                }
                $header = \pack('vvV', $recVerInstance, $recType, \strlen($dggData));
                $innerData .= $header . $dggData;
                // write the bstoreContainer
                if ($bstoreContainer = $this->object->getBstoreContainer()) {
                    $writer = new self($bstoreContainer);
                    $innerData .= $writer->close();
                }
                // write the record
                $recVer = 0xf;
                $recInstance = 0x0;
                $recType = 0xf000;
                $length = \strlen($innerData);
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $this->data = $header . $innerData;
                break;
            case BstoreContainer::class:
                // this is a container record
                // initialize
                $innerData = '';
                // treat the inner data
                if ($BSECollection = $this->object->getBSECollection()) {
                    foreach ($BSECollection as $BSE) {
                        $writer = new self($BSE);
                        $innerData .= $writer->close();
                    }
                }
                // write the record
                $recVer = 0xf;
                $recInstance = \count($this->object->getBSECollection());
                $recType = 0xf001;
                $length = \strlen($innerData);
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $this->data = $header . $innerData;
                break;
            case BSE::class:
                // this is a semi-container record
                // initialize
                $innerData = '';
                // here we treat the inner data
                if ($blip = $this->object->getBlip()) {
                    $writer = new self($blip);
                    $innerData .= $writer->close();
                }
                // initialize
                $data = '';
                /** @scrutinizer ignore-call */
                $btWin32 = $this->object->getBlipType();
                /** @scrutinizer ignore-call */
                $btMacOS = $this->object->getBlipType();
                $data .= \pack('CC', $btWin32, $btMacOS);
                $rgbUid = \pack('VVVV', 0, 0, 0, 0);
                // todo
                $data .= $rgbUid;
                $tag = 0;
                $size = \strlen($innerData);
                $cRef = 1;
                $foDelay = 0;
                //todo
                $unused1 = 0x0;
                $cbName = 0x0;
                $unused2 = 0x0;
                $unused3 = 0x0;
                $data .= \pack('vVVVCCCC', $tag, $size, $cRef, $foDelay, $unused1, $cbName, $unused2, $unused3);
                $data .= $innerData;
                // write the record
                $recVer = 0x2;
                /** @scrutinizer ignore-call */
                $recInstance = $this->object->getBlipType();
                $recType = 0xf007;
                $length = \strlen($data);
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $this->data = $header;
                $this->data .= $data;
                break;
            case Blip::class:
                // this is an atom record
                // write the record
                switch ($this->object->getParent()->getBlipType()) {
                    case BSE::BLIPTYPE_JPEG:
                        // initialize
                        $innerData = '';
                        $rgbUid1 = \pack('VVVV', 0, 0, 0, 0);
                        // todo
                        $innerData .= $rgbUid1;
                        $tag = 0xff;
                        // todo
                        $innerData .= \pack('C', $tag);
                        $innerData .= $this->object->getData();
                        $recVer = 0x0;
                        $recInstance = 0x46a;
                        $recType = 0xf01d;
                        $length = \strlen($innerData);
                        $recVerInstance = $recVer;
                        $recVerInstance |= $recInstance << 4;
                        $header = \pack('vvV', $recVerInstance, $recType, $length);
                        $this->data = $header;
                        $this->data .= $innerData;
                        break;
                    case BSE::BLIPTYPE_PNG:
                        // initialize
                        $innerData = '';
                        $rgbUid1 = \pack('VVVV', 0, 0, 0, 0);
                        // todo
                        $innerData .= $rgbUid1;
                        $tag = 0xff;
                        // todo
                        $innerData .= \pack('C', $tag);
                        $innerData .= $this->object->getData();
                        $recVer = 0x0;
                        $recInstance = 0x6e0;
                        $recType = 0xf01e;
                        $length = \strlen($innerData);
                        $recVerInstance = $recVer;
                        $recVerInstance |= $recInstance << 4;
                        $header = \pack('vvV', $recVerInstance, $recType, $length);
                        $this->data = $header;
                        $this->data .= $innerData;
                        break;
                }
                break;
            case DgContainer::class:
                // this is a container record
                // initialize
                $innerData = '';
                // write the dg
                $recVer = 0x0;
                /** @scrutinizer ignore-call */
                $recInstance = $this->object->getDgId();
                $recType = 0xf008;
                $length = 8;
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                // number of shapes in this drawing (including group shape)
                $countShapes = \count($this->object->getSpgrContainerOrThrow()->getChildren());
                $innerData .= $header . \pack('VV', $countShapes, $this->object->getLastSpId());
                // write the spgrContainer
                if ($spgrContainer = $this->object->getSpgrContainer()) {
                    $writer = new self($spgrContainer);
                    $innerData .= $writer->close();
                    // get the shape offsets relative to the spgrContainer record
                    $spOffsets = $writer->getSpOffsets();
                    $spTypes = $writer->getSpTypes();
                    // save the shape offsets relative to dgContainer
                    foreach ($spOffsets as &$spOffset) {
                        $spOffset += 24;
                        // add length of dgContainer header data (8 bytes) plus dg data (16 bytes)
                    }
                    $this->spOffsets = $spOffsets;
                    $this->spTypes = $spTypes;
                }
                // write the record
                $recVer = 0xf;
                $recInstance = 0x0;
                $recType = 0xf002;
                $length = \strlen($innerData);
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $this->data = $header . $innerData;
                break;
            case SpgrContainer::class:
                // this is a container record
                // initialize
                $innerData = '';
                // initialize spape offsets
                $totalSize = 8;
                $spOffsets = [];
                $spTypes = [];
                // treat the inner data
                foreach ($this->object->getChildren() as $spContainer) {
                    $writer = new self($spContainer);
                    $spData = $writer->close();
                    $innerData .= $spData;
                    // save the shape offsets (where new shape records begin)
                    $totalSize += \strlen($spData);
                    $spOffsets[] = $totalSize;
                    $spTypes = \array_merge($spTypes, $writer->getSpTypes());
                }
                // write the record
                $recVer = 0xf;
                $recInstance = 0x0;
                $recType = 0xf003;
                $length = \strlen($innerData);
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $this->data = $header . $innerData;
                $this->spOffsets = $spOffsets;
                $this->spTypes = $spTypes;
                break;
            case SpContainer::class:
                // initialize
                $data = '';
                // build the data
                // write group shape record, if necessary?
                if ($this->object->getSpgr()) {
                    $recVer = 0x1;
                    $recInstance = 0x0;
                    $recType = 0xf009;
                    $length = 0x10;
                    $recVerInstance = $recVer;
                    $recVerInstance |= $recInstance << 4;
                    $header = \pack('vvV', $recVerInstance, $recType, $length);
                    $data .= $header . \pack('VVVV', 0, 0, 0, 0);
                }
                /** @scrutinizer ignore-call */
                $this->spTypes[] = $this->object->getSpType();
                // write the shape record
                $recVer = 0x2;
                /** @scrutinizer ignore-call */
                $recInstance = $this->object->getSpType();
                // shape type
                $recType = 0xf00a;
                $length = 0x8;
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $data .= $header . \pack('VV', $this->object->getSpId(), $this->object->getSpgr() ? 0x5 : 0xa00);
                // the options
                if ($this->object->getOPTCollection()) {
                    $optData = '';
                    $recVer = 0x3;
                    $recInstance = \count($this->object->getOPTCollection());
                    $recType = 0xf00b;
                    foreach ($this->object->getOPTCollection() as $property => $value) {
                        $optData .= \pack('vV', $property, $value);
                    }
                    $length = \strlen($optData);
                    $recVerInstance = $recVer;
                    $recVerInstance |= $recInstance << 4;
                    $header = \pack('vvV', $recVerInstance, $recType, $length);
                    $data .= $header . $optData;
                }
                // the client anchor
                if ($this->object->getStartCoordinates()) {
                    $recVer = 0x0;
                    $recInstance = 0x0;
                    $recType = 0xf010;
                    // start coordinates
                    [$column, $row] = Coordinate::indexesFromString($this->object->getStartCoordinates());
                    $c1 = $column - 1;
                    $r1 = $row - 1;
                    // start offsetX
                    /** @scrutinizer ignore-call */
                    $startOffsetX = $this->object->getStartOffsetX();
                    // start offsetY
                    /** @scrutinizer ignore-call */
                    $startOffsetY = $this->object->getStartOffsetY();
                    // end coordinates
                    [$column, $row] = Coordinate::indexesFromString($this->object->getEndCoordinates());
                    $c2 = $column - 1;
                    $r2 = $row - 1;
                    // end offsetX
                    /** @scrutinizer ignore-call */
                    $endOffsetX = $this->object->getEndOffsetX();
                    // end offsetY
                    /** @scrutinizer ignore-call */
                    $endOffsetY = $this->object->getEndOffsetY();
                    $clientAnchorData = \pack('vvvvvvvvv', $this->object->getSpFlag(), $c1, $startOffsetX, $r1, $startOffsetY, $c2, $endOffsetX, $r2, $endOffsetY);
                    $length = \strlen($clientAnchorData);
                    $recVerInstance = $recVer;
                    $recVerInstance |= $recInstance << 4;
                    $header = \pack('vvV', $recVerInstance, $recType, $length);
                    $data .= $header . $clientAnchorData;
                }
                // the client data, just empty for now
                if (!$this->object->getSpgr()) {
                    $clientDataData = '';
                    $recVer = 0x0;
                    $recInstance = 0x0;
                    $recType = 0xf011;
                    $length = \strlen($clientDataData);
                    $recVerInstance = $recVer;
                    $recVerInstance |= $recInstance << 4;
                    $header = \pack('vvV', $recVerInstance, $recType, $length);
                    $data .= $header . $clientDataData;
                }
                // write the record
                $recVer = 0xf;
                $recInstance = 0x0;
                $recType = 0xf004;
                $length = \strlen($data);
                $recVerInstance = $recVer;
                $recVerInstance |= $recInstance << 4;
                $header = \pack('vvV', $recVerInstance, $recType, $length);
                $this->data = $header . $data;
                break;
        }
        return $this->data;
    }
    /**
     * Gets the shape offsets.
     *
     * @return array
     */
    public function getSpOffsets()
    {
        return $this->spOffsets;
    }
    /**
     * Gets the shape types.
     *
     * @return array
     */
    public function getSpTypes()
    {
        return $this->spTypes;
    }
}
