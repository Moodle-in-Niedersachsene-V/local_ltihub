<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_ltihub\local;

use enrol_lti\local\ltiadvantage\lib\launch_cache_session;
use enrol_lti\local\ltiadvantage\repository\application_registration_repository;
use enrol_lti\local\ltiadvantage\repository\deployment_repository;
use enrol_lti\local\ltiadvantage\utility\message_helper;

/**
 * The validated LTI 1.3 deep linking launch of the current user ("Select content").
 *
 * Enrol_lti validates the id_token in enrol/lti/launch_deeplink.php and keeps the decoded
 * claims in the session launch cache. This class reads the newest cached deep linking launch
 * through enrol_lti's own cache class and checks that the platform user is bound to the
 * logged-in Moodle user. The session key layout is internal to enrol_lti, so this class must
 * be re-checked after every Moodle upgrade.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launch_context {
    /** @var string Claim holding the message type. */
    public const CLAIM_MESSAGETYPE = 'https://purl.imsglobal.org/spec/lti/claim/message_type';

    /** @var string Claim holding the platform context (course). */
    public const CLAIM_CONTEXT = 'https://purl.imsglobal.org/spec/lti/claim/context';

    /** @var string Claim holding the deployment id. */
    public const CLAIM_DEPLOYMENT = 'https://purl.imsglobal.org/spec/lti/claim/deployment_id';

    /**
     * Constructor.
     *
     * @param string $launchid Key of the launch in the session launch cache.
     * @param array $claims Decoded and validated launch claims.
     * @param int $deploymentid Id of the enrol_lti_deployment record.
     * @param string $platformname Name of the platform registration.
     */
    protected function __construct(
        /** @var string Key of the launch in the session launch cache. */
        protected string $launchid,
        /** @var array Decoded and validated launch claims. */
        protected array $claims,
        /** @var int Id of the enrol_lti_deployment record. */
        protected int $deploymentid,
        /** @var string Name of the platform registration. */
        protected string $platformname
    ) {
    }

    /**
     * Find the newest deep linking launch of the current user.
     *
     * @return self|null Null if the session holds no deep linking launch of this user.
     */
    public static function from_session(): ?self {
        global $SESSION, $USER;

        if (empty($SESSION->enrol_lti_launch) || !is_array($SESSION->enrol_lti_launch)) {
            return null;
        }

        $cache = new launch_cache_session();
        foreach (array_reverse(array_keys($SESSION->enrol_lti_launch)) as $launchid) {
            $claims = $cache->getLaunchData((string) $launchid);
            if (!is_array($claims) || ($claims[self::CLAIM_MESSAGETYPE] ?? null) !== 'LtiDeepLinkingRequest') {
                continue;
            }
            if (empty($claims['iss']) || empty($claims['sub']) || empty($claims[self::CLAIM_DEPLOYMENT])) {
                continue;
            }

            // The cached launch must belong to the user who is logged in now.
            $bounduserid = get_auth_plugin('lti')->get_user_binding($claims['iss'], $claims['sub']);
            if ((int) $bounduserid !== (int) $USER->id) {
                continue;
            }

            $clientid = is_array($claims['aud']) ? ($claims['azp'] ?? reset($claims['aud'])) : $claims['aud'];
            $registration = (new application_registration_repository())->find_by_platform($claims['iss'], $clientid);
            if (!$registration) {
                continue;
            }
            $deployment = (new deployment_repository())->find_by_registration(
                $registration->get_id(),
                $claims[self::CLAIM_DEPLOYMENT]
            );
            if (!$deployment) {
                continue;
            }
            return new self((string) $launchid, $claims, $deployment->get_id(), $registration->get_name());
        }
        return null;
    }

    /**
     * Key of the launch in the session launch cache, as expected by launch_deeplink.php?launchid=.
     *
     * @return string
     */
    public function get_launchid(): string {
        return $this->launchid;
    }

    /**
     * Whether the launch was made by an instructor or administrator of the platform course.
     *
     * @return bool
     */
    public function is_instructor(): bool {
        return message_helper::is_instructor_launch($this->claims);
    }

    /**
     * Whether the launch came from a platform course (context claim present).
     *
     * @return bool
     */
    public function has_platform_context(): bool {
        return isset($this->claims[self::CLAIM_CONTEXT]['id']) && $this->claims[self::CLAIM_CONTEXT]['id'] !== '';
    }

    /**
     * The id of the platform course.
     *
     * @return string
     */
    public function get_platform_contextid(): string {
        return (string) ($this->claims[self::CLAIM_CONTEXT]['id'] ?? '');
    }

    /**
     * Title of the platform course, falling back to its label.
     *
     * @return string
     */
    public function get_platform_contexttitle(): string {
        $context = $this->claims[self::CLAIM_CONTEXT] ?? [];
        return trim((string) ($context['title'] ?? $context['label'] ?? ''));
    }

    /**
     * Issuer of the platform, usually the wwwroot of the school instance.
     *
     * @return string
     */
    public function get_issuer(): string {
        return (string) $this->claims['iss'];
    }

    /**
     * Key of the hub course: one per teacher and platform course.
     *
     * Hub accounts are bound by issuer and platform user id, so the key stays the same
     * whichever registration of the same school instance the teacher uses.
     *
     * @return string Sha256 hex digest.
     */
    public function get_platformkey(): string {
        return hash('sha256', implode("\n", [$this->get_issuer(), $this->get_platform_contextid(), (string) $this->claims['sub']]));
    }

    /**
     * Id of the enrol_lti_deployment record.
     *
     * @return int
     */
    public function get_deploymentid(): int {
        return $this->deploymentid;
    }

    /**
     * Name of the school instance, taken from the registration name.
     *
     * @return string
     */
    public function get_platformlabel(): string {
        return trim($this->platformname);
    }
}
