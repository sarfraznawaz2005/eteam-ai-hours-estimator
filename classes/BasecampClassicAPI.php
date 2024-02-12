<?php

/**
 * User: Sarfraz
 * Date: 11/02/2024
 * Time: 1:54 PM
 *
 * Some functions to get data from basecamp classic.
 * Docs: https://github.com/basecamp/basecamp-classic-api
 *
 */

class BasecampClassicAPI
{
    private static $companyId = '732202';
    private static $companyName = 'eteamid';
    private static $userAPIToken = '0ddc5efa6908b0df2abd7fe68d5096fdd7d55a26';
    private static $userEmail = 'mr-x@eteamid.com';
    private static $eteamMiscTasksProjectName = 'ETeam Miscellaneous Tasks';
    private static $eteamKnowledgeSharingProjectName = 'eTeam Knowledge Sharing';

    public static function getCurlInstance(): CurlHandle | bool
    {
        $session = curl_init();

        curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($session, CURLOPT_USERAGENT, static::$companyName . ".basecamphq.com (" . static::$userEmail . ")");
        curl_setopt($session, CURLOPT_USERPWD, static::$userAPIToken . ":X");
        curl_setopt($session, CURLOPT_HTTPHEADER, ['Accept: application/xml', 'Content-Type: application/xml']);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($session, CURLOPT_FOLLOWLOCATION, false);

        return $session;
    }

    public static function getInfo($action, string $queryString = ''): array | string
    {
        $url = 'https:/' . static::$companyName . '.basecamphq.com/' . $action . '/' . $queryString;

        $session = static::getCurlInstance();
        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HTTPGET, true);
        curl_setopt($session, CURLOPT_HEADER, false);

        $response = curl_exec($session);
        curl_close($session);

        @$response = simplexml_load_string($response);
        $response = (array) $response;

        //$array = json_decode(json_encode($response), 1);

        if (isset($response['head']['title'])) {
            return '';
        }

        return $response;
    }

    public static function postInfo($action, $xmlData): array | bool
    {
        $url = 'https://' . static::$companyName . '.basecamphq.com/' . $action;

        $session = static::getCurlInstance();
        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HEADER, true);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, $xmlData);

        $data = curl_exec($session);

        curl_close($session);

        return [
            'code' => curl_getinfo($session, CURLINFO_HTTP_CODE),
            'content' => $data,
        ];
    }

    public static function deleteResource($action): int | bool
    {
        $url = 'https://' . static::$companyName . '.basecamphq.com/' . $action;

        $session = static::getCurlInstance();
        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'DELETE');

        curl_exec($session);
        curl_close($session);

        return curl_getinfo($session, CURLINFO_HTTP_CODE);
    }

    public static function getResourceCreatedId($content): array | string | null
    {
        preg_match('#location: .+#', $content, $matches);

        if (isset($matches[0])) {
            $id = @array_slice(explode('/', $matches[0]), -1)[0];

            return preg_replace('/\D/', '', $id);
        }

        return '';
    }

    public static function getAllProjects(): array
    {
        $finalData = [];

        $data = static::getInfo("projects");

        if (isset($data['project'])) {

            $project = (array) $data['project'];

            if (isset($project[0])) {
                foreach ($data['project'] as $xml) {
                    $array = (array) $xml;

                    if (isset($array['id'], $array['company']) && $array['status'] === 'active') {
                        $finalData[$array['id']] = ucwords($array['name']);
                    }
                }
            } else if (isset($project['id'], $project['company']) && $project['status'] === 'active') {
                $finalData[$project['id']] = ucwords($project['name']);
            }

        }

        asort($finalData);

        return $finalData;
    }

    public static function getAllUsers(array $excludedUserIds = []): array
    {
        $finalData = [];

        $data = static::getInfo("people");

        if (isset($data['person'])) {

            // for when single record is returned
            $entry = (array) $data['person'];

            if (isset($entry['id'], $entry['first-name'])) {
                $finalData[$entry['id']] = ucwords($entry['first-name']) . ' ' . ucwords($entry['last-name']);
            } else {
                foreach ($data['person'] as $xml) {
                    $array = (array) $xml;

                    // consider only company employees
                    if ($array['company-id'] !== static::$companyId) {
                        continue;
                    }

                    if (isset($array['first-name'])) {

                        if ($excludedUserIds && in_array($array['id'], $excludedUserIds, true)) {
                            continue;
                        }

                        $finalData[$array['id']] = ucwords($array['first-name']) . ' ' . ucwords($array['last-name']);
                    }
                }
            }
        }

        asort($finalData);

        return $finalData;
    }

    public static function getAllMessages($projectId): array
    {
        $finalData = [];

        $data = static::getInfo("/projects/$projectId/posts.xml");

        if (isset($data['post'])) {

            $post = (array) $data['post'];

            if (isset($post[0])) {
                foreach ($data['post'] as $xml) {
                    $array = (array) $xml;

                    if (isset($array['id'])) {
                        $finalData[$array['id']] = ucwords($array['title']);
                    }
                }
            } else if (isset($post['id'])) {
                $finalData[$post['id']] = ucwords($post['title']);
            }

        }

        return $finalData;
    }

    public static function getEteamMiscTasksProjectId()
    {
        $projects = static::getAllProjects();

        return array_search(static::$eteamMiscTasksProjectName, $projects);
    }

    public static function getEteamKnowledgeSharingProjectId()
    {
        $projects = static::getAllProjects();

        return array_search(static::$eteamKnowledgeSharingProjectName, $projects);
    }
}
