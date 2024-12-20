<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Pdf
 */

namespace ZendPdf\Outline;

use Countable;
use RecursiveIterator;
use ZendPdf as Pdf;
use ZendPdf\Exception;
use ZendPdf\InternalType;
use ZendPdf\ObjectFactory;

/**
 * Abstract PDF outline representation class
 *
 * @todo Implement an ability to associate an outline item with a structure element (PDF 1.3 feature)
 *
 * @package    Zend_PDF
 * @subpackage Zend_PDF_Outline
 */
abstract class AbstractOutline implements
    Countable,
    RecursiveIterator
{
    /**
     * True if outline is open.
     *
     * @var boolean
     */
    protected $_open = false;
    protected $_title;
    protected $_color;
    protected $_italic;
    protected $_bold;
    protected $_target;

    /**
     * Array of child outlines (array of \ZendPdf\Outline\AbstractOutline objects)
     *
     * @var array
     */
    public $childOutlines = array();


    /**
     * Get outline title.
     *
     * @return string
     */
    abstract public function getTitle();

    /**
     * Set outline title
     *
     * @param string $title
     * @return \ZendPdf\Outline\AbstractOutline
     */
    abstract public function setTitle($title);

    /**
     * Returns true if outline item is open by default
     *
     * @return boolean
     */
    public function isOpen()
    {
        return $this->_open;
    }

    /**
     * Sets 'isOpen' outline flag
     *
     * @param boolean $isOpen
     * @return \ZendPdf\Outline\AbstractOutline
     */
    public function setIsOpen($isOpen)
    {
        $this->_open = $isOpen;
        return $this;
    }

    /**
     * Returns true if outline item is displayed in italic
     *
     * @return boolean
     */
    abstract public function isItalic();

    /**
     * Sets 'isItalic' outline flag
     *
     * @param boolean $isItalic
     * @return \ZendPdf\Outline\AbstractOutline
     */
    abstract public function setIsItalic($isItalic);

    /**
     * Returns true if outline item is displayed in bold
     *
     * @return boolean
     */
    abstract public function isBold();

    /**
     * Sets 'isBold' outline flag
     *
     * @param boolean $isBold
     * @return \ZendPdf\Outline\AbstractOutline
     */
    abstract public function setIsBold($isBold);


    /**
     * Get outline text color.
     *
     * @return \ZendPdf\Color\Rgb
     */
    abstract public function getColor();

    /**
     * Set outline text color.
     * (null means default color which is black)
     *
     * @param \ZendPdf\Color\Rgb $color
     * @return \ZendPdf\Outline\AbstractOutline
     */
    abstract public function setColor(Pdf\Color\Rgb $color);

    /**
     * Get outline target.
     *
     * @return \ZendPdf\InternalStructure\NavigationTarget
     */
    abstract public function getTarget();

    /**
     * Set outline target.
     * Null means no target
     *
     * @param \ZendPdf\InternalStructure\NavigationTarget|string $target
     * @return \ZendPdf\Outline\AbstractOutline
     */
    abstract public function setTarget($target = null);

    /**
     * Get outline options
     *
     * @return array
     */
    public function getOptions()
    {
        return array('title'  => $this->_title,
                     'open'   => $this->_open,
                     'color'  => $this->_color,
                     'italic' => $this->_italic,
                     'bold'   => $this->_bold,
                     'target' => $this->_target);
    }

    /**
     * Set outline options
     *
     * @param array $options
     * @return \ZendPdf\Action\AbstractAction
     * @throws \ZendPdf\Exception\ExceptionInterface
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'title':
                    $this->setTitle($value);
                    break;

                case 'open':
                    $this->setIsOpen($value);
                    break;

                case 'color':
                    $this->setColor($value);
                    break;
                case 'italic':
                    $this->setIsItalic($value);
                    break;

                case 'bold':
                    $this->setIsBold($value);
                    break;

                case 'target':
                    $this->setTarget($value);
                    break;

                default:
                    throw new Exception\InvalidArgumentException("Unknown option name - '$key'.");
                    break;
            }
        }

        return $this;
    }

    /**
     * Create new Outline object
     *
     * It provides two forms of input parameters:
     *
     * 1. \ZendPdf\Outline\AbstractOutline::create(string $title[, \ZendPdf\InternalStructure\NavigationTarget $target])
     * 2. \ZendPdf\Outline\AbstractOutline::create(array $options)
     *
     * Second form allows to provide outline options as an array.
     * The followed options are supported:
     *   'title'  - string, outline title, required
     *   'open'   - boolean, true if outline entry is open (default value is false)
     *   'color'  - \ZendPdf\Color\Rgb object, true if outline entry is open (default value is null - black)
     *   'italic' - boolean, true if outline entry is displayed in italic (default value is false)
     *   'bold'   - boolean, true if outline entry is displayed in bold (default value is false)
     *   'target' - \ZendPdf\InternalStructure\NavigationTarget object or string, outline item destination
     *
     * @return \ZendPdf\Outline\AbstractOutline
     * @throws \ZendPdf\Exception\ExceptionInterface
     */
    public static function create($param1, $param2 = null)
    {
        if (is_string($param1)) {
            if ($param2 !== null  &&  !($param2 instanceof Pdf\InternalStructure\NavigationTarget  ||  is_string($param2))) {
                throw new Exception\InvalidArgumentException('Outline create method takes $title (string) and $target (\ZendPdf\InternalStructure\NavigationTarget or string) or an array as an input');
            }

            return new Created(array('title'  => $param1,
                                     'target' => $param2));
        } else {
            if (!is_array($param1)  ||  $param2 !== null) {
                throw new Exception\InvalidArgumentException('Outline create method takes $title (string) and $destination (\ZendPdf\InternalStructure\NavigationTarget) or an array as an input');
            }

            return new Created($param1);
        }
    }

    /**
     * Returns number of the total number of open items at all levels of the outline.
     *
     * @internal
     * @return integer
     */
    public function openOutlinesCount()
    {
        $count = 1; // Include this outline

        if ($this->isOpen()) {
            foreach ($this->childOutlines as $child) {
                $count += $child->openOutlinesCount();
            }
        }

        return $count;
    }

    /**
     * Dump Outline and its child outlines into PDF structures
     *
     * Returns dictionary indirect object or reference
     *
     * @param \ZendPdf\ObjectFactory    $factory object factory for newly created indirect objects
     * @param boolean $updateNavigation  Update navigation flag
     * @param \ZendPdf\InternalType\AbstractTypeObject $parent   Parent outline dictionary reference
     * @param \ZendPdf\InternalType\AbstractTypeObject $prev     Previous outline dictionary reference
     * @param SplObjectStorage $processedOutlines  List of already processed outlines
     * @return \ZendPdf\InternalType\AbstractTypeObject
     */
    abstract public function dumpOutline(ObjectFactory $factory,
                                                       $updateNavigation,
                       InternalType\AbstractTypeObject $parent,
                       InternalType\AbstractTypeObject $prev = null,
                                     \SplObjectStorage $processedOutlines = null);


    ////////////////////////////////////////////////////////////////////////
    //  RecursiveIterator interface methods
    //////////////

    /**
     * Returns the child outline.
     *
     * @return \ZendPdf\Outline\AbstractOutline
     */
    public function current()
    {
        return current($this->childOutlines);
    }

    /**
     * Returns current iterator key
     *
     * @return integer
     */
    public function key()
    {
        return key($this->childOutlines);
    }

    /**
     * Go to next child
     */
    public function next()
    {
        return next($this->childOutlines);
    }

    /**
     * Rewind children
     */
    public function rewind()
    {
        return reset($this->childOutlines);
    }

    /**
     * Check if current position is valid
     *
     * @return boolean
     */
    public function valid()
    {
        return current($this->childOutlines) !== false;
    }

    /**
     * Returns the child outline.
     *
     * @return \ZendPdf\Outline\AbstractOutline|null
     */
    public function getChildren()
    {
        return current($this->childOutlines);
    }

    /**
     * Implements RecursiveIterator interface.
     *
     * @return bool  whether container has any pages
     */
    public function hasChildren()
    {
        return count($this->childOutlines) > 0;
    }


    ////////////////////////////////////////////////////////////////////////
    //  Countable interface methods
    //////////////

    /**
     * count()
     *
     * @return int
     */
    public function count()
    {
        return count($this->childOutlines);
    }
}
