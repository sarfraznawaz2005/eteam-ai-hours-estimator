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
    public static $userId = '13043551';
    private static $companyId = '732202';
    private static $companyName = 'eteamid';
    private static $userAPIToken = '0ddc5efa6908b0df2abd7fe68d5096fdd7d55a26';
    private static $userEmail = 'mr-x@eteamid.com';
    private static $eteamMiscTasksProjectName = 'ETeam Miscellaneous Tasks';
    private static $eteamKnowledgeSharingProjectName = 'ETeam Knowledge Sharing';

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
        $url = 'https://' . static::$companyName . '.basecamphq.com/' . $action . '/' . $queryString;

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
        $storage = new DateTimeBasedStorage(__FUNCTION__);

        $data = $storage->read();

        if ($data) {
            return $data;
        }

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

        $storage->save($finalData);

        return $finalData;

    }

    public static function getAllUsers(array $excludedUserIds = []): array
    {
        $storage = new DateTimeBasedStorage(__FUNCTION__);

        $data = $storage->read();

        if ($data) {
            return $data;
        }

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

        $storage->save($finalData);

        return $finalData;
    }

    // returns 25 most recent messages by default
    public static function getAllMessages($projectId): array
    {
        /*
        $storage = new DateTimeBasedStorage(__FUNCTION__ . '_' . $projectId, 'time');

        $data = $storage->read();

        if ($data) {
            //logMessage('reading saved messages');
            return $data;
        }
        */

        $finalData = [];

        $data = static::getInfo("/projects/$projectId/posts.xml");

        if (isset($data['post'])) {

            $post = (array) $data['post'];

            if (isset($post[0])) {
                foreach ($data['post'] as $xml) {
                    $array = (array) $xml;

                    if (isset($array['id'])) {
                        $finalData[$array['id']] = [
                            'id' => $array['id'],
                            'title' => $array['title'],
                            'body' => $array['body'],
                            'author-id' => $array['author-id'],
                            'author-name' => $array['author-name'],
                            'posted-on' => $array['posted-on'],
                        ];
                    }
                }

                uasort($finalData, function ($a, $b) {
                    return $b['id'] - $a['id']; // id descending
                });

            } else if (isset($post['id'])) {
                $finalData[$post['id']] = [
                    'id' => $post['id'],
                    'title' => $post['title'],
                    'body' => $post['body'],
                    'author-id' => $post['author-id'],
                    'author-name' => $post['author-name'],
                    'posted-on' => $post['posted-on'],
                ];
            }

        }

        //$storage->save($finalData);

        return $finalData;
    }

    public static function getAllComments($postId): array
    {
        /*
        // too many files were created involving i/o operations
        $storage = new DateTimeBasedStorage(__FUNCTION__ . '_' . $postId, 'time');

        $data = $storage->read();

        if ($data) {
            //logMessage('reading saved comments');
            return $data;
        }
        */

        $finalData = [];

        $data = static::getInfo("/posts/$postId/comments.xml");

        if (isset($data['comment'])) {

            $comment = (array) $data['comment'];

            if (isset($comment[0])) {
                foreach ($data['comment'] as $xml) {
                    $array = (array) $xml;

                    if (isset($array['id'])) {
                        $finalData[$array['id']] = [
                            'id' => $array['id'],
                            'body' => $array['body'],
                            'author-id' => $array['author-id'],
                            'author-name' => $array['author-name'],
                            'created-at' => $array['created-at'],
                        ];
                    }
                }

                uasort($finalData, function ($a, $b) {
                    return $b['id'] - $a['id']; // id descending
                });

            } else if (isset($comment['id'])) {
                $finalData[$comment['id']] = [
                    'id' => $comment['id'],
                    'body' => $comment['body'],
                    'author-id' => $comment['author-id'],
                    'author-name' => $comment['author-name'],
                    'created-at' => $comment['created-at'],
                ];
            }

        }

        //$storage->save($finalData);

        return $finalData;
    }

    public static function getAllMessagesForAllProjectsParallel(): array
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $responses = [];

        $projectIds = array_keys(static::getAllProjects());

        // Initiate multiple curl handles for each project
        foreach ($projectIds as $projectId) {
            $session = static::getCurlInstance();
            $url = 'https://' . static::$companyName . '.basecamphq.com/projects/' . $projectId . '/posts.xml';

            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_HTTPGET, true);
            curl_setopt($session, CURLOPT_HEADER, false);

            curl_multi_add_handle($multiHandle, $session);
            $curlHandles[$projectId] = $session;
        }

        // Execute the handles
        $running = null;

        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        // Collect responses and remove handles
        foreach ($curlHandles as $projectId => $session) {
            $response = curl_multi_getcontent($session);
            @$responseXml = simplexml_load_string($response);

            if ($responseXml) {
                $responseArray = (array) $responseXml;
                $responses[$projectId] = $responseArray['post'] ?? [];
            } else {
                $responses[$projectId] = [];
            }

            curl_multi_remove_handle($multiHandle, $session);
            curl_close($session);
        }

        curl_multi_close($multiHandle);

        // Process responses
        $finalData = [];
        foreach ($responses as $projectId => $data) {
            $projectMessages = [];

            if (is_array($data)) {
                foreach ($data as $xml) {
                    $array = (array) $xml;

                    if (isset($array['id'])) {
                        $projectMessages[$array['id']] = [
                            'id' => $array['id'],
                            'title' => $array['title'],
                            'body' => $array['body'],
                            'author-id' => $array['author-id'],
                            'author-name' => $array['author-name'],
                            'posted-on' => $array['posted-on'],
                        ];
                    }
                }

                uasort($projectMessages, function ($a, $b) {
                    return $b['id'] - $a['id']; // Sort by id descending
                });

            } else if (isset($data['id'])) {
                $projectMessages[$data['id']] = [
                    'id' => $data['id'],
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'author-id' => $data['author-id'],
                    'author-name' => $data['author-name'],
                    'posted-on' => $data['posted-on'],
                ];
            }

            $finalData[$projectId] = $projectMessages;
        }

        return $finalData;
    }

    public static function getAllCommentsForAllPostsForAllProjectsParallel(): array
    {
        $allPosts = static::getAllMessagesForAllProjectsParallel();

        $projectPosts = [];

        foreach ($allPosts as $projectId => $posts) {
            if (!empty($posts)) {
                $projectPosts[$projectId] = array_keys($posts); // Extract post IDs
            }
        }

        // Now, fetch comments for all these posts in parallel
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        // Prepare and add curl handles for each post in each project
        foreach ($projectPosts as $projectId => $postIds) {
            foreach ($postIds as $postId) {
                $session = static::getCurlInstance();

                $url = 'https://' . static::$companyName . '.basecamphq.com/posts/' . $postId . '/comments.xml';

                curl_setopt($session, CURLOPT_URL, $url);
                curl_setopt($session, CURLOPT_HTTPGET, true);
                curl_setopt($session, CURLOPT_HEADER, false);

                curl_multi_add_handle($multiHandle, $session);
                $curlHandles[$projectId . '_' . $postId] = $session;
            }
        }

        // Execute the handles in parallel
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        // Collect and process responses
        $finalCommentsData = [];
        foreach ($curlHandles as $key => $session) {
            $response = curl_multi_getcontent($session);
            @$responseXml = simplexml_load_string($response);

            $comments = [];
            if ($responseXml && isset($responseXml->comment)) {
                foreach ($responseXml->comment as $comment) {
                    $array = (array) $comment;

                    $comments[$array['id']] = [
                        'id' => $array['id'],
                        'body' => $array['body'],
                        'author-id' => $array['author-id'],
                        'author-name' => $array['author-name'],
                        'created-at' => $array['created-at'],
                    ];
                }
            }

            // Sort comments by ID in descending order
            uasort($comments, function ($a, $b) {
                return $b['id'] <=> $a['id'];
            });

            list($projectId, $postId) = explode('_', $key);

            if (!isset($finalCommentsData[$projectId])) {
                $finalCommentsData[$projectId] = [];
            }

            $finalCommentsData[$projectId][$postId] = $comments;
            curl_close($session);
        }

        curl_multi_close($multiHandle);

        return $finalCommentsData;
    }

    public static function getEteamMiscTasksProjectId()
    {
        $projects = static::getAllProjects();

        $projects = array_map('strtolower', $projects);

        return array_search(strtolower(static::$eteamMiscTasksProjectName), $projects);
    }

    public static function getEteamKnowledgeSharingProjectId()
    {
        $projects = static::getAllProjects();

        $projects = array_map('strtolower', $projects);

        return array_search(strtolower(static::$eteamKnowledgeSharingProjectName), $projects);
    }

    public static function getUrlContents(string $url)
    {
        $session = curl_init();

        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_USERAGENT, static::$companyName . ".basecamphq.com (" . static::$userEmail . ")");
        curl_setopt($session, CURLOPT_USERPWD, static::$userAPIToken . ":X");
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($session, CURLOPT_FOLLOWLOCATION, false);

        $output = curl_exec($session);
        curl_close($session);

        return $output;
    }

}
