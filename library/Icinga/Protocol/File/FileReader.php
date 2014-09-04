<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\File;

use FilterIterator;
use Iterator;
use Zend_Config;
use Icinga\Protocol\File\FileReaderException;
use Icinga\Util\File;

/**
 * Read file line by line
 */
class FileReader extends FilterIterator
{
    /**
     * A PCRE string with the fields to extract from the file's lines as named subpatterns
     *
     * @var string
     */
    protected $fields;

    /**
     * An associative array of the current line's fields ($field => $value)
     *
     * @var array
     */
    protected $currentData;

    /**
     * Create a new reader
     *
     * @param   Zend_Config $config
     *
     * @throws  FileReaderException If a required $config directive (filename or fields) is missing
     */
    public function __construct(Zend_Config $config)
    {
        foreach (array('filename', 'fields') as $key) {
            if (! isset($config->{$key})) {
                throw new FileReaderException('The directive `%s\' is required', $key);
            }
        }
        $this->fields = $config->fields;
        $f = new File($config->filename);
        $f->setFlags(
            File::DROP_NEW_LINE |
            File::READ_AHEAD |
            File::SKIP_EMPTY
        );
        parent::__construct($f);
    }

    /**
     * Return the current data
     *
     * @return array
     */
    public function current()
    {
        return $this->currentData;
    }

    /**
     * Accept lines matching the given PCRE pattern
     *
     * @return bool
     *
     * @throws FileReaderException  If PHP failed parsing the PCRE pattern
     */
    public function accept()
    {
        $data = array();
        $matched = @preg_match(
            $this->fields,
            $this->getInnerIterator()->current(),
            $data
        );
        if ($matched === false) {
            throw new FileReaderException('Failed parsing regular expression!');
        } else if ($matched === 1) {
            foreach ($data as $key => $value) {
                if (is_int($key)) {
                    unset($data[$key]);
                }
            }
            $this->currentData = $data;
            return true;
        }
        return false;
    }

    /**
     * Instantiate a FileQuery object
     *
     * @return FileQuery
     */
    public function select()
    {
        return new FileQuery($this);
    }

    /**
     * Return the number of available valid lines.
     *
     * @return int
     */
    public function count()
    {
        return iterator_count($this);
    }

    /**
     * Fetch result as an array of objects
     *
     * @param   FileQuery $query
     *
     * @return  array
     */
    public function fetchAll(FileQuery $query)
    {
        $all = array();
        foreach ($this->fetchPairs($query) as $index => $value) {
            $all[$index] = (object) $value;
        }
        return $all;
    }

    /**
     * Fetch result as a key/value pair array
     *
     * @param   FileQuery $query
     *
     * @return  array
     */
    public function fetchPairs(FileQuery $query)
    {
        $skip = $query->getOffset();
        $read = $query->getLimit();
        if ($skip === null) {
            $skip = 0;
        }
        $lines = array();
        if ($query->sortDesc()) {
            $count = $this->count($query);
            if ($count <= $skip) {
                return $lines;
            } else if ($count < ($skip + $read)) {
                $read = $count - $skip;
                $skip = 0;
            } else {
                $skip = $count - ($skip + $read);
            }
        }
        $index = 0;
        foreach ($this as $line) {
            if ($index >= $skip) {
                if ($index >= $skip + $read) {
                    break;
                }
                $lines[] = $line;
            }
            ++$index;
        }
        if ($query->sortDesc()) {
            $lines = array_reverse($lines);
        }
        return $lines;
    }

    /**
     * Fetch first result row
     *
     * @param   FileQuery $query
     *
     * @return  object
     */
    public function fetchRow(FileQuery $query)
    {
        $all = $this->fetchAll($query);
        if (isset($all[0])) {
            return $all[0];
        }
        return null;
    }

    /**
     * Fetch first result column
     *
     * @param   FileQuery $query
     *
     * @return  array
     */
    public function fetchColumn(FileQuery $query)
    {
        $column = array();
        foreach ($this->fetchPairs($query) as $pair) {
            foreach ($pair as $value) {
                $column[] = $value;
                break;
            }
        }
        return $column;
    }

    /**
     * Fetch first column value from first result row
     *
     * @param   FileQuery $query
     *
     * @return  mixed
     */
    public function fetchOne(FileQuery $query)
    {
        $pairs = $this->fetchPairs($query);
        if (isset($pairs[0])) {
            foreach ($pairs[0] as $value) {
                return $value;
            }
        }
        return null;
    }
}