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

