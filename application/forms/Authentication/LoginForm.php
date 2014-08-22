<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Authentication;

use Icinga\Web\Form;
use Icinga\Web\Url;

/**
 * Class LoginForm
 */
class LoginForm extends Form
{
    /**
     * Initialize this login form
     */
    public function init()
    {
        $this->setName('form_login');
        $this->setSubmitLabel(t('Login'));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements($formData)
    {
        return array(
            $this->createElement(
                'text',
                'username',
                array(
                    'required'      => true,
                    'label'         => t('Username'),
                    'placeholder'   => t('Please enter your username...'),
                    'class'         => false === isset($formData['username']) ? 'autofocus' : ''
                )
            ),
            $this->createElement(
                'password',
                'password',
                array(
                    'required'      => true,
                    'label'         => t('Password'),
                    'placeholder'   => t('...and your password'),
                    'class'         => isset($formData['username']) ? 'autofocus' : ''
                )
            ),
            $this->createElement(
                'hidden',
                'redirect',
                array(
                    'value' => Url::fromRequest()->getParam('redirect')
                )
            )
        );
    }
}
