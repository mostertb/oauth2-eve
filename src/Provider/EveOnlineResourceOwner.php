<?php

namespace Killmails\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class EveOnlineResourceOwner implements ResourceOwnerInterface
{

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id (character id).
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->response['CharacterID'] ?: null;
    }

    /**
     * Get character id. Alias of getId().
     *
     * @return int|null
     */
    public function getCharacterID()
    {
        return $this->getId();
    }

    /**
     * Get resource owner name (character name).
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->response['CharacterName'] ?: null;
    }

    /**
     * Get character name. Alias of getName().
     *
     * @return string|null
     */
    public function getCharacterName()
    {
        return $this->getName();
    }

    /**
     * Get character owner hash.
     *
     * @return string|null
     */
    public function getCharacterOwnerHash()
    {
        return $this->response['CharacterOwnerHash'];
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
