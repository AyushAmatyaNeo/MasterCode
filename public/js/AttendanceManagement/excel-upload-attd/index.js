(function ($, app) {
    "use strict";
    $(document).ready(function () {
    	let $employeeIdBased = $("#employeeIdBased");
    	let $employeeCodeBased = $("#employeeCodeBased");
    	let $table = $("#table");
    	var excelData;
        var finalData = [];
        let basedOnFlag = 1;
    	$("select").select2();
        var columns = [];
    	columns.push({field: "A", title: "EMPLOYEE ID", width: 80});
    	columns.push({field: "B", title: "NAME", width: 120});
    	columns.push({field: "C", title: "DATE", width: 120});
    	columns.push({field: "D", title: "CHECK IN", width: 120});
    	columns.push({field: "E", title: "CHECK OUT", width: 120});

        var columns2 = [];
        columns2.push({field: "A", title: "EMPLOYEE CODE", width: 80});
    	columns2.push({field: "B", title: "NAME", width: 120});
    	columns2.push({field: "C", title: "DATE", width: 120});
        columns2.push({field: "D", title: "CHECK IN", width: 120});
    	columns2.push({field: "E", title: "CHECK OUT", width: 120});

        var columns3 = [];
        columns3.push({field: "B", title: "Thumb Id", width: 80});
    	columns3.push({field: "A", title: "NAME", width: 120});
    	columns3.push({field: "E", title: "DATE", width: 120});
        columns3.push({field: "F", title: "Time", width: 120});

    	app.initializeKendoGrid($table, columns);
        $("#employeeIdBased").click(function(){ 
            basedOnFlag = 1; 
            $table.empty();
            app.initializeKendoGrid($table, columns);});
        $("#employeeCodeBased").click(function(){ 
            basedOnFlag = 2; 
            $table.empty();
            app.initializeKendoGrid($table, columns2);
        });
        $("#hikvision").click(function(){ 
            basedOnFlag = 3; 
            $table.empty();
            app.initializeKendoGrid($table, columns3);
        });

    	


    	$("#submit").on('click', function(){
			if(prompt("Make sure all options are correctly selected. Type CONFIRM to proceed.") !== "CONFIRM"){ return; }
            var fileUploadedFlag = document.getElementById("excelImport").files.length;
            if(fileUploadedFlag == 0){
                app.showMessage('File is missing', 'warning');
                return;
            }
            if(basedOnFlag==3){
                app.serverRequest(document.insertAttendanceLink, {data : finalData, basedOn: basedOnFlag}).then(function(){
                    app.showMessage('Operation successfull', 'success');
                }, function (error) {
                    console.log(error);
                });
            }else{
                app.serverRequest(document.insertAttendanceLink, {data : excelData, basedOn: basedOnFlag}).then(function(){
                    app.showMessage('Operation successfull', 'success');
                }, function (error) {
                    console.log(error);
                });
            }
    	});

      	$("#excelImport").change(function(evt){
            var selectedFile = evt.target.files[0];
            var reader = new FileReader();
            reader.onload = function(event) {
              var data = event.target.result;
              var workbook = XLSX.read(data, {
                  type: 'binary'
              });
              workbook.SheetNames.forEach(function(sheetName) {
				  var XL_row_object = XLSX.utils.sheet_to_json(workbook.Sheets.Sheet1, {header: "A"}); 
				  var json_object = JSON.stringify(XL_row_object);
                  excelData = JSON.parse(json_object);
                  console.log(excelData);
                  var start_data = 0;
                  var headingIndex =-1;
                  
                  $.each(excelData,function(index, value){
                    if (value.A == 'Name') 
                    {
                        start_data=1;
                        headingIndex= index;
                    }
                    if (start_data == 1 && headingIndex != index) 
                    {
                        finalData.push(value);
                    }
				  });
					
                  if(basedOnFlag==3){
                    app.renderKendoGrid($table, finalData);
                  }else{
                    app.renderKendoGrid($table, excelData);
                  }
                });
            }
            reader.onerror = function(event) {
              console.error("File could not be read! Code " + event.target.error.code);
            };
            reader.readAsBinaryString(selectedFile);
      	});
    });
})(window.jQuery, window.app);