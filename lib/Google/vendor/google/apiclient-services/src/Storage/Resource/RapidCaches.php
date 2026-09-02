<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Storage\Resource;

use Google\Service\Storage\GoogleLongrunningOperation;
use Google\Service\Storage\RapidCache;
use Google\Service\Storage\RapidCaches as RapidCachesModel;

/**
 * The "rapidCaches" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google\Service\Storage(...);
 *   $rapidCaches = $storageService->rapidCaches;
 *  </code>
 */
class RapidCaches extends \Google\Service\Resource
{
  /**
   * Disables a Rapid Cache instance. (rapidCaches.disable)
   *
   * @param string $bucket Name of the parent bucket.
   * @param string $rapidCacheId The ID of the requested Rapid Cache instance.
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function disable($bucket, $rapidCacheId, $optParams = [])
  {
    $params = ['bucket' => $bucket, 'rapidCacheId' => $rapidCacheId];
    $params = array_merge($params, $optParams);
    return $this->call('disable', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Returns the metadata of a Rapid Cache instance. (rapidCaches.get)
   *
   * @param string $bucket Name of the parent bucket.
   * @param string $rapidCacheId The ID of the requested Rapid Cache instance.
   * @param array $optParams Optional parameters.
   * @return RapidCache
   * @throws \Google\Service\Exception
   */
  public function get($bucket, $rapidCacheId, $optParams = [])
  {
    $params = ['bucket' => $bucket, 'rapidCacheId' => $rapidCacheId];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], RapidCache::class);
  }
  /**
   * Creates a Rapid Cache instance. (rapidCaches.insert)
   *
   * @param string $bucket Name of the parent bucket.
   * @param RapidCache $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function insert($bucket, RapidCache $postBody, $optParams = [])
  {
    $params = ['bucket' => $bucket, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('insert', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Returns a list of Rapid Cache instances of the bucket.
   * (rapidCaches.listRapidCaches)
   *
   * @param string $bucket Name of the parent bucket.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Maximum number of items to return in a single page of
   * responses.
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @return RapidCachesModel
   * @throws \Google\Service\Exception
   */
  public function listRapidCaches($bucket, $optParams = [])
  {
    $params = ['bucket' => $bucket];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], RapidCachesModel::class);
  }
  /**
   * Updates the configuration of a Rapid Cache instance. (rapidCaches.update)
   *
   * @param string $bucket Name of the parent bucket.
   * @param string $rapidCacheId The ID of the requested Rapid Cache instance.
   * @param RapidCache $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function update($bucket, $rapidCacheId, RapidCache $postBody, $optParams = [])
  {
    $params = ['bucket' => $bucket, 'rapidCacheId' => $rapidCacheId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('update', [$params], GoogleLongrunningOperation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RapidCaches::class, 'Google_Service_Storage_Resource_RapidCaches');
