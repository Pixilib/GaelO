/**
 Copyright (C) 2018-2020 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

 const UppyCsvPlugin = class UppyCsvPlugin extends Uppy.Core.Plugin {

  constructor (uppy, opts) {
    super(uppy, opts)
    this.id = opts.id
    this.type = opts.type
    this.checkCSV = this.checkCSV.bind(this)

    this.patientId = opts.patientId
    this.studyDate = new Date(opts.studyDate)
    this.suvLo = opts.suvLo
    this.suvHigh = opts.suvHigh
    this.useSuv = opts.useSuv
    this.useCT = opts.useCT
    this.notify = opts.notify

  }

  checkCSV(filesIDs){

      let file = this.uppy.getFile(filesIDs[0])

      let csvParser = new PetCtCSVParser(file.data)

      return csvParser.parseCSV().then( () => {

        let checkPatientIdentity = csvParser.checkAcquisition(this.patientId, this.studyDate)
        let checkThreshold = csvParser.checkTMTVThreshold(this.suvLo)

        this.notify({
          Tmtv : csvParser.getTmtvValue(),
          suvLo : csvParser.getSuvLow(),
          checkPatientIdentity : checkPatientIdentity,
          checkThreshold : checkThreshold
        })

        return (checkPatientIdentity && checkThreshold)
      }).then((resultCheck)=> {
        if(!resultCheck) this.uppy.removeFile(file.id)
        else this.uppy.emit('preprocess-complete', file.id)
      }).catch( () => {
        this.notify({
          otherError : 'Error Reading CSV'
        })
        this.uppy.removeFile(file.id)
      })

  }

  install () {
    this.uppy.addPreProcessor(this.checkCSV)
  }

  uninstall () {
    this.uppy.removePreProcessor(this.checkCSV)
  }

}
