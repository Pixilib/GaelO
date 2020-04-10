const uppyCsvPlugin = class uppyCsvPlugin extends Uppy.Core.Plugin {
  constructor (uppy, opts) {
    super(uppy, opts)
    this.id = opts.id
    this.type = opts.type
    this.checkCSV = this.checkCSV.bind(this)
  }

  checkCSV(filesIDs){
      console.log(filesIDs)
      let file = uppy.getFile(filesIDs[0])
      console.log('emit upload')
      uppy.removeFile(filesIDs[0])
      

  }

  install () {
    this.uppy.addPreProcessor(this.checkCSV)
  }

  uninstall () {
    this.uppy.removePreProcessor(this.checkCSV)
  }
}
