<?php

namespace App\GaelO\Entities;

class NotificationEntity {
    public string $id;
    public string $object;
    public string $message;

    public static function fillFromDBReponseArray(array $array): NotificationEntity
    {
        $documentationEntity  = new NotificationEntity();
        $documentationEntity->id = $array['id'];
        $documentationEntity->object = $array['data']['object'];
        $documentationEntity->message = $array['data']['message'];

        return $documentationEntity;
    }
}