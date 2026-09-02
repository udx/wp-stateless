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

namespace Google\Service\Storage;

class RapidCachePolicy extends \Google\Model
{
  /**
   * Ingestion on write is explicitly enabled for the managed folder.
   */
  public const INGEST_ON_WRITE_enabled = 'enabled';
  /**
   * Ingestion on write isn't specified at the managed folder level and is
   * inherited from the parent resource's configuration. This is the default
   * value.
   */
  public const INGEST_ON_WRITE_unspecified = 'unspecified';
  /**
   * The ingest-on-write policy for objects in the managed folder. When set to
   * `enabled`, objects are automatically ingested into the cache when they are
   * written to the managed folder.
   *
   * @var string
   */
  public $ingestOnWrite;
  /**
   * The unique identifier of the rapid cache.
   *
   * @var string
   */
  public $rapidCacheId;

  /**
   * The ingest-on-write policy for objects in the managed folder. When set to
   * `enabled`, objects are automatically ingested into the cache when they are
   * written to the managed folder.
   *
   * Accepted values: enabled, unspecified
   *
   * @param self::INGEST_ON_WRITE_* $ingestOnWrite
   */
  public function setIngestOnWrite($ingestOnWrite)
  {
    $this->ingestOnWrite = $ingestOnWrite;
  }
  /**
   * @return self::INGEST_ON_WRITE_*
   */
  public function getIngestOnWrite()
  {
    return $this->ingestOnWrite;
  }
  /**
   * The unique identifier of the rapid cache.
   *
   * @param string $rapidCacheId
   */
  public function setRapidCacheId($rapidCacheId)
  {
    $this->rapidCacheId = $rapidCacheId;
  }
  /**
   * @return string
   */
  public function getRapidCacheId()
  {
    return $this->rapidCacheId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RapidCachePolicy::class, 'Google_Service_Storage_RapidCachePolicy');
