class PetCtCSVParser {

    constructor(csvFile){
        this.csvFile = csvFile
    }

    parseCSV(){
        let self = this

        return new Promise(function(complete, error) {
            Papa.parse(self.csvFile, {complete, error});
        }).then(results => {
            self.papaResultArray = results.data
        }).then( () => {
            self._extractLineOfInterest()
        });

    }

    _extractLineOfInterest(){
        let parsedCSV = this.papaResultArray

        for(let i = 0 ; i < parsedCSV.length ; i++){
            //Search for empty line, the patient identification is just the line before
            if( parsedCSV[i].length>1 && parsedCSV[i][1].includes(" sum") ) {
                this.patientIdentificationLine = (i+1)
            }
            if( parsedCSV[i][0]=="SUVlo" ) {
                this.roiThresholdLine = (i+1)
            }
        }
        console.log(this.papaResultArray)
    }

    getTmtvValue(){
        return this.papaResultArray[(this.patientIdentificationLine - 1)][3]
    }
    getPatientId(){
        return this.papaResultArray[this.patientIdentificationLine][13]
    }

    getAcquisitionDate(){
        let dateString = this.papaResultArray[this.patientIdentificationLine][1].trim()
        let date = moment(dateString, "MMM D_YYYY").toDate();
        return date
    }

    getSuvLow(){
        return this.papaResultArray[this.roiThresholdLine][0]
    }

    getsuvHigh(){
        return this.papaResultArray[this.roiThresholdLine][1]
    }

    isUseSUV(){
        return Boolean(parseInt(this.papaResultArray[this.roiThresholdLine][4]))
    }

    isUseCT(){
        return Boolean(parseInt(this.papaResultArray[this.roiThresholdLine][5]))
    }

    checkAcquisition(patientId, date){
        return (this.getPatientId() == patientId && this.getAcquisitionDate().getTime() === date.getTime())
    }

    checkTMTVThreshold(suvLo, suvHigh=100){
        let checkSuvLo = true
        if(suvLo != null) checkSuvLo = (this.getSuvLow() == suvLo)
        return ( checkSuvLo && this.getsuvHigh()>=suvHigh && !this.isUseCT() && this.isUseSUV() )
    }
}

