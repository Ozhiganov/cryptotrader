<?php
/**
 * cryptotrader
 * Copyright (C) 2018 Domingo Oropeza
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\HttpClient\Message;

use GuzzleHttp\Psr7\Response as BaseResponse;

class Response extends BaseResponse
{
    /**
     * Get API rate limits from the response headers.
     *
     * @return array
     */
    public function getApiRateLimit()
    {
        return [
            'limit' => (string)$this->getHeader('Rate-Limit-Total'),
            'remaining' => (string)$this->getHeader('Rate-Limit-Remaining'),
            'reset' => (string)$this->getHeader('Rate-Limit-Reset'),
        ];
    }

    /**
     * Get the decoded body from the response.
     *
     * @return mixed
     */
    public function getContent()
    {
        $body = $this->getBody();
        $content = json_decode($body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return $body;
        }

        return $content;
    }

    /**
     * Get parsed content range header.
     *
     * @return array
     */
    public function getContentRange()
    {
        $contentRange = (string)$this->getHeader('Content-Range');
        if (!$contentRange) {
            return null;
        }
        $matches = [];
        preg_match('/^.*\ (\d*)-(\d*)\/(\d*)$/', $contentRange, $matches);

        return [
            'count' => (int)$matches[3],
            'end' => (int)$matches[2],
            'start' => (int)$matches[1],
        ];
    }

    /**
     * Get response error.
     *
     * @return string
     */
    public function getError()
    {
        $content = $this->getContent();
        if (!is_array($content)) {
            return 'unknown_error';
        }
        if (!empty($content['error'])) {
            return $content['error'];
        }
        if (!empty($content['code'])) {
            return $content['code'];
        }

        return 'unknown_error';
    }

    /**
     * Get error description.
     *
     * @return string
     */
    public function getErrorDescription()
    {
        $content = $this->getContent();
        if (!is_array($content)) {
            return 'An unknown error occurred';
        }
        if (!empty($content['errors'])) {
            // We need to use `json_encode` since there can be a multidimensional array.
            return sprintf('Errors: %s', json_encode($content['errors']));
        }
        if (!empty($content['error_description'])) {
            return $content['error_description'];
        }
        if (!empty($content['message'])) {
            return $content['message'];
        }

        return 'An unknown error occurred';
    }

    /**
     * Checks if the response is a client error.
     *
     * @return boolean
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Checks if the response is a server error.
     *
     * @return boolean
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
}