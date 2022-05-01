

# TEST Study specifications

Study: TEST  
Prefix: 170000

---

## Visits

### Visit Group: FDG

#### PET_0
+ **Local Form** :heavy_check_mark:  
    + Expected data :
    ```javascript
    {
        comments: string (optional)
    }
    ```
+ **QC** 100  
+ **Review** 100  
+ **Adjudication** :x: 
+ **Optional** :x:   
+ **Limit days** : 
    + Low: -300
    + Up: 0


### Visit Group: WB

#### CT0
+ **Local Form** :heavy_check_mark:  
    + Expected data: See [PET_0](#pet0)
+ **QC** : 100 
+ **Review** : 100 
    + Expected data: See [PET_0](#pet0)
+ **Adjudication** :x: 
+ **Optional** :x:   
+ **Limit days** : 
    + Low: -300
    + Up: 0