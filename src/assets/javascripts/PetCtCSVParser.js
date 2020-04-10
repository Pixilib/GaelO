class PetCtCSVParser {

    constructor(papaResultArray){
        this.papaResultArray = papaResultArray
        this._extractLineOfInterest()
    }

    _extractLineOfInterest(){

        let parsedCSV = this.papaResultArray

        for(let i = 0 ; i < parsedCSV.length ; i++){
            //Search for empty line, the patient identification is just the line before
            if( parsedCSV[i].length===13 && parsedCSV[i][1].includes("sum") ) {
                this.patientIdentificationLine = (i+1)
            }
            if( parsedCSV[i][0]=="SUVlo" ) {
                this.roiThresholdLine = (i+1)
            }
        }

    }

    getPatientId(){
        return this.papaResultArray[this.patientIdentificationLine][13]
    }

    getDateString(){
        return this.papaResultArray[this.patientIdentificationLine][1].trim()
    }

    getSuvLow(){
        return this.papaResultArray[this.roiThresholdLine][0]
    }

    getsuvHigh(){
        return this.papaResultArray[this.roiThresholdLine][1]
    }

    isUseSUV(){
        return this.papaResultArray[this.roiThresholdLine][4]
    }

    isUseCT(){
        return this.papaResultArray[this.roiThresholdLine][5]
    }

    checkAcquisition(patientId, date){

    }

    checkTMTVThreshold(suvLo){

    }
}

Papa.parsePromise = function(file) {
    return new Promise(function(complete, error) {
      Papa.parse(file, {complete, error});
    });
  };