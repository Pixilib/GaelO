queue -> allume et eteint l'aci get le status grace a AzureService
      -> envoie les dicom  grace au GaelOProcessingService
      -> recupere les dicoms traité
      -> envoye signal pour mailing admin ( si soucis ACI )
      -> envoie signal Mailing ( disponibilité de la dicom etc)
