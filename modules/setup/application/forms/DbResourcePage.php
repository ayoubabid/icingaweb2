<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Forms;

use PDOException;
use Icinga\Web\Form;
use Icinga\Forms\Config\Resource\DbResourceForm;
use Icinga\Module\Setup\Utils\DbTool;

/**
 * Wizard page to define connection details for a database resource
 */
class DbResourcePage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_db_resource');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'type',
            array(
                'required'  => true,
                'value'     => 'db'
            )
        );
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => mt('setup', 'Database Resource', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => mt(
                    'setup',
                    'Now please configure your database resource. Note that the database itself does not need to'
                    . ' exist at this time as it is going to be created once the wizard is about to be finished.'
                )
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        } else {
            $this->addElement(
                'hidden',
                'skip_validation',
                array(
                    'required'  => true,
                    'value'     => 0
                )
            );
        }

        $resourceForm = new DbResourceForm();
        $this->addElements($resourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icingaweb_db');
        $this->addElement(
            'hidden',
            'prefix',
            array(
                'required'  => true,
                'value'     => 'icingaweb_'
            )
        );
    }

    /**
     * Validate the given form data and check whether it's possible to connect to the database server
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (false === isset($data['skip_validation']) || $data['skip_validation'] == 0) {
            try {
                $db = new DbTool($this->getValues());
                $db->checkConnectivity();
            } catch (PDOException $e) {
                $this->addError($e->getMessage());
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the connection validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => mt('setup', 'Skip Validation'),
                'description'   => mt('setup', 'Check this to not to validate connectivity with the given database server')
            )
        );
    }
}
