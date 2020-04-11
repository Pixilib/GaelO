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
      console.log(filesIDs)
      let file = this.uppy.getFile(filesIDs[0])
      console.log(file)

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
        console.log(checkPatientIdentity)
        console.log(checkThreshold)
        return (checkPatientIdentity && checkThreshold)
      }).then((resultCheck)=> {
        if(!resultCheck) this.uppy.removeFile(file.id)
        else this.uppy.emit('preprocess-complete', file.id)

      })

  }

  install () {
    this.uppy.addPreProcessor(this.checkCSV)
  }

  uninstall () {
    this.uppy.removePreProcessor(this.checkCSV)
  }

}
