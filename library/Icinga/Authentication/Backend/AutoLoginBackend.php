<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Authentication\UserBackend;
use Icinga\Data\ConfigObject;
use Icinga\User;

/**
 * Test login with external authentication mechanism, e.g. Apache
 */
class AutoLoginBackend extends UserBackend
{
    /**
     * Regexp expression to strip values from a username
     *
     * @var string
     */
    private $stripUsernameRegexp;

    /**
     * Create new autologin backend
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config)
    {
        $this->stripUsernameRegexp = $config->get('strip_username_regexp');
    }

    /**
     * Count the available users
     *
     * Autologin backends will always return 1
     *
     * @return int
     */
    public function count()
    {
        return 1;
    }

    /**
     * Test whether the given user exists
     *
     * @param   User $user
     *
     * @return  bool
     */
    public function hasUser(User $user)
    {
        if (isset($_SERVER['REMOTE_USER'])) {
            $username = $_SERVER['REMOTE_USER'];
            $user->setRemoteUserInformation($username, 'REMOTE_USER');
            if ($this->stripUsernameRegexp) {
                $stripped = preg_replace($this->stripUsernameRegexp, '', $username);
                if ($stripped !== false) {
                    // TODO(el): PHP issues a warning when PHP cannot compile the regular expression. Should we log an
                    // additional message in that case?
                    $username = $stripped;
                }
            }
            $user->setUsername($username);
            return true;
        }

        return false;
    }

    /**
     * Authenticate
     *
     * @param   User    $user
     * @param   string  $password
     *
     * @return  bool
     */
    public function authenticate(User $user, $password = null)
    {
        return $this->hasUser($user);
    }
}
