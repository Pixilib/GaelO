<?php

namespace App\GaelO\Entities;

class NotificationEntity {
    public string $id;
    public string $object;
    public string $message;
    public bool $read;
    public string $creationDate;

    public static function fillFromDBReponseArray(array $array): NotificationEntity
    {
        $documentationEntity  = new NotificationEntity();
        $documentationEntity->id = $array['id'];
        $documentationEntity->object = $array['data']['object'];
        $documentationEntity->message = $array['data']['message'];
        $documentationEntity->read = $array['read_at'] ? true : false;
        $documentationEntity->creationDate = $array['created_at'];

        return $documentationEntity;
    }
}