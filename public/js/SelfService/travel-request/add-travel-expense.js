(function ($, app) {
    $(document).ready(function () {
        let total = 150;
        var index = 1;

        // $("#domesticConfigTable").hide();

        $("#addDomesticBtn").on('click', function (){
            $("#domesticConfigTable").show();
            $(".arrDate:first").attr("required", "required");
            $(".depDate:first").attr("required", "required");
            $(".locFrom:first").attr("required", "required");
            $(".locto:first").attr("required", "required");
        });
        $("#deleteDomesticBtn").on('click', function (){
            $("#domesticConfigTable").hide();
            $(".arrDate:first").prop('required',false);
            $(".depDate:first").prop('required',false);
            $(".locFrom:first").prop('required',false);
            $(".locto:first").prop('required',false);
        });
          
        $(document).on('change', '.tableForAll .amountExpL', function(e) {
            var test = 0;
            var conversion = 1;
            var mul = 1;
            $(".amountExpL").each(function(){
                var t2 = $(this).val();
                conversion =  $(this).closest("tr").find("td:eq(6) input[type='number']").val();
                mul = eval(t2) * eval(conversion);
                test = eval(test + mul)
               });

           $('#totalAmountExp').val(test);
        });
        $(document).on('change', '.tableForAll .conversionRateL1', function(e) {
            console.log('here');
            var test = 0;
            var conversion = 1;
            var mul = 1;
            $(".conversionRateL1").each(function(){
                var conversion = $(this).val();
                t2 =  $(this).closest("tr").find("td:eq(8) input[type='number']").val();
                mul = eval(t2) * eval(conversion);
                test = eval(test + mul)
               });

           $('#totalAmountExp').val(test);
        });



        $(document).on('change', '.tableForAll .amountExpI', function(e) {
            var test = 0;
            var conversion = 1;
            var mul = 1;
            $(".amountExpI").each(function(){
                var t2 = $(this).val();
                conversion =  $(this).closest("tr").find("td:eq(7) input[type='number']").val();
                mul = eval(t2) * eval(conversion);
                test = eval(test + mul)
               });
           $('#totalAmountExpI').val(test);
        });

        $(document).on('change', '.tableForAll .exchangeRateInternational', function(e) {
            console.log('here');
            var test = 0;
            var conversion = 1;
            var mul = 1;
            $(".exchangeRateInternational").each(function(){
                var conversion = $(this).val();
                t2 =  $(this).closest("tr").find("td:eq(8) input[type='number']").val();
                mul = eval(t2) * eval(conversion);
                test = eval(test + mul)
               });

           $('#totalAmountExp').val(test);
        });
        $("#addInternationalBtn").on('click', function (){
            $("#internationalConfigTable").show();
            $(".arrDateInternational:first").attr("required", "required");
            $(".depDateInternational:first").attr("required", "required");
            $(".locFromInternational:first").attr("required", "required");
            $(".loctoInternational:first").attr("required", "required")
        });
        $("#deleteInternationalBtn").on('click', function (){
            $("#internationalConfigTable").hide();
            $(".arrDateInternational:first").prop('required',false);
            $(".depDateInternational:first").prop('required',false);
            $(".locFromInternational:first").prop('required',false);
            $(".loctoInternational:first").prop('required',false);
        });

        internationalPlaces = [
            {
                "CODE": "LISTED CITIES",
                "NAME": "Listed Cities"
            },
            {
                "CODE": "OTHER INDIA CITIES",
                "NAME": "Other India Cities"
            },
            {
                "CODE": "OTHER COUNTRIES",
                "NAME": "Other Countries"
            }
        ]
        transportTypes = [
            {
                "CODE": "WALKING",
                "NAME": "Walking"
            },
            {
                "CODE": "TRAVEL",
                "NAME": "Travel"
            }
            
        ];
        
        all_data=document.currencyList;


        // all_data_json = JSON.parse(all_data);


        app.startEndDatePickerWithNepali('', 'departureDate', '', 'returnedDate');
        // app.addComboTimePicker($('.depTime'), $('.arrTime'));
        app.populateSelect($('.mot'), transportTypes, 'CODE', 'NAME', '-select-',null, 1, true);
        app.addDatePicker($('.depDate:last'), $('.arrDate:last'));
        // app.addDatePicker($('#fromDate'));
        // app.addComboTimePicker($('.depTimeInternational'), $('.arrTimeInternational'));
        app.populateSelect($('.motInternational'), internationalPlaces, 'CODE', 'NAME', '-select-',null, 1, true);
        app.populateSelect($('.currency'), all_data, 'code', 'code', '-select-',null, 1, true);
        app.addDatePicker($('.depDateInternational:last'), $('.arrDateInternational:last'));
       
      
        // $(".depDate:first").on('change', function () {
        //     var diff =  Math.floor(( Date.parse($(".arrDate:first").val()) - Date.parse($(".depDate:first").val()) ) / 86400000);
        //     $(".noOfDays:first").val(diff + 1);
        // });
        // $(document).on('change', '.otherExpenses', function(){
        //     $(this).closest("tr").find("td div input.total").val($(this).closest("tr").find("td div input.otherExpenses").val());
        // });
        $(document).on('change', '.depDateInternational, .arrDateInternational', function () {
            var diff = Math.floor(( Date.parse($(this).closest("tr").find("td div input.arrDateInternational").val()) - Date.parse($(this).closest("tr").find("td div input.depDateInternational").val()) ) / 86400000);
            console.log(diff);
            $(this).closest("tr").find("td div input.noOfDaysInternational").val(diff + 1);
        });

        $(document).on('change', ".depDate, .arrDate", function () {
            var diff = Math.floor(( Date.parse($(this).closest("tr").find("td div input.arrDate").val()) - Date.parse($(this).closest("tr").find("td div input.depDate").val()) ) / 86400000);
            $(this).closest("tr").find("td div input.noOfDays").val(diff + 1);
        });


        $('form').bind('submit', function () {
            $(this).find(':disabled').removeAttr('disabled');
        });

        $('.deatilAddBtn').on('click', function () {
            var appendData = `
            <tr>
                <td><input class="dtlDelBtn btn btn-danger" type="button" value="Del -" style="padding:3px;"></td>
                <td>
                                                <select name="erTypeL[]"  class="erTypeL form-control" data-id="`+index+`" id="ertyprL_`+index+`" style="width:13rem ;">
                                                    <option value="">Select ER Type</option>
                                                    <option value="EP">Employeee Paid</option>
                                                    <option value="EP">Company Paid</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="locFromL[]" data-id="`+index+`" id="locFromL_`+index+`"   class="locFrom form-control" style="width:10rem ;"> 
                                            </td>
                                           
                                            <td>
                                                <input type="text" name="arrDateL[]"  data-id="`+index+`" id="arrDateL_`+index+`" class="arrDate form-control" style="width:12rem ;"> 
                                            </td>
                                            
                                            <td>
                                                <input type="text" name="ticketNoL[]" data-id="`+index+`" id="ticketNoL_`+index+`" class="ticketNo form-control" style="width:12rem ;"> 
                                            </td> 
                                           
                                            <td>
                                                <select name="expenseHeadL[]" data-id="`+index+`" id="expenseHead_`+index+`" class="form-control" >
                                                <option value="-1">---select expense head---</option>
                                                <option value="Accommodation">Accommodation</option>
                                                <option value ="Airfare">Airfare</option>
                                                <option value ="Auto Maintenance">Auto Maintenance</option>
                                                <option value ="Airport Taxes and visas etc">Airport Taxes and visas etc</option>
                                                <option value ="Black Berry (Monthly Usage Bill)">Black Berry (Monthly Usage Bill)</option>
                                                <option value ="Books Newspaper & Subscription">Books Newspaper & Subscription</option>
                                                <option value ="Cafeteria Expenses-admin">Cafeteria Expenses-admin</option>
                                                <option value="Car hire">Car hire</option>
                                                <option value ="Club Membership Fee">Club Membership Fee</option>
                                                <option value ="Conveyance- Car Rental">Conveyance- Car Rental</option>
                                                <option value ="Conveyance - Other">Conveyance - Other</option>
                                                <option value ="Daily Allowance">Daily Allowance</option>
                                                <option value ="Data Card">Data Card</option>
                                                <option value="Expenses">Expenses</option>
                                                <option value ="Entertainment">Entertainment</option>
                                                <option value ="Expatriate Benefits">Expatriate Benefits</option>
                                                <option value ="Expatriate CLA Expenses">Expatriate CLA Expenses</option>
                                                <option value ="Gasoline Diesel Fuel Oil">Gasoline Diesel Fuel Oil</option>
                                                <option value ="Guest House Expenses">Guest House Expenses</option>
                                                <option value ="Hotel Meals (inclusive of Tips)">Hotel Meals (inclusive of Tips)</option>
                                                <option value ="IS Information (others)">IS Information (others)</option>
                                                <option value ="Lab Testing and Certification Cost">Lab Testing and Certification Cost</option>
                                                <option value ="Laundry">Laundry</option>
                                                <option value ="Lodging">Lodging</option>
                                                <option value ="Meeting">Meeting</option>
                                                <option value ="Miscellaneous Expenses">Miscellaneous Expenses</option>
                                                <option value ="Mobile Handset Reimbursement">Mobile Handset Reimbursement</option>
                                                <option value ="Mobile Phone Expenses">Mobile Phone Expenses</option>
                                                <option value ="Other Tips">Other Tips</option>
                                                <option value ="Postage & Courier Charge">Postage & Courier Charge</option>
                                                <option value ="Reimb on Stamp Paper">Reimb on Stamp Paper</option>
                                                <option value ="Relocation Expenses">Relocation Expenses</option>
                                                <option value ="Stationary">Stationary</option>
                                                <option value ="Supplies General">Supplies General</option>
                                                <option value ="Taxi/Bus/Car rental (inc fuel & conv allow)">Taxi/Bus/Car rental (inc fuel & conv allow)</option>
                                                <option value ="Telephone/Fax Expenses">Telephone/Fax Expenses</option>
                                                <option value ="Train Fare/Bus Fare">Train Fare/Bus Fare</option>
                                                <option value ="Ticket - Others">Ticket - Others</option>
                                                <option value ="Toll Fees">Toll Fees</option>
                                                <option value ="Training Expenses">Training Expenses</option>
                                                <option value ="Uniforms and Towels -Admin">Uniforms and Towels -Admin</option>
                                                </select> 
                                            </td>
                                            <td>
                                                <input type="number" name="conversionRateL[]" data-id="`+index+`" id="conversionRateL_`+index+`" value="1" step="any"  style="width:10rem ;" disabled>
                                            </td>
                                            <td>
                                                <input type="text" name="currencyL[]" data-id="`+index+`" id="currencyL_`+index+`" value="NPR" style="width:10rem ;" disabled >
                                            </td>
                            
                                            <td>
                                                <input type="number" name="amountExpL[]" data-id="`+index+`" id="amountExpL_`+index+`" class="amountExpL" step="any">
                                            </td>
                                            <td>
                                                <div style="width:150px">
                                                    <textarea name="detRemarksL[]" data-id="`+index+`" id="detRemarksL_`+index+`" class="detRemarks" ></textarea>
                                                </div>
                                            </td>
            </tr>
            `;
            var exhLtr = document.expenseItrHeads;
            $('#domesticConfigTable tbody').append(appendData);
            all_data=document.currencyList;
            app.populateSelect($('#currencyL_'+index), all_data, 'code', 'code', '-select-',null, 1, true);
            $('#ertyprL_'+index).prop('required',true);
            $('#locFromL_'+index).prop('required',true);
            $('#arrDateL_'+index).prop('required',true);
            $('#expenseHead_'+index).prop('required',true);
            $('#amountExpL_'+index).prop('required',true);
            index += 1;
            // console.log(exhLtr);
            // app.populateSelect($('#domesticConfigTable tbody').find('.expenseheadLtr'),exhLtr, 'gl', 'name', '-select-',null, 1, true);
            // app.addComboTimePicker(
            //         $('#domesticConfigTable tbody').find('.depTime:last'),
            //         $('#domesticConfigTable tbody').find('.arrTime:last')
            //         );
            
            app.addDatePicker(
                    $('#domesticConfigTable tbody').find('.depDate:last'),
                    $('#domesticConfigTable tbody').find('.arrDate:last')
                    );
             
            app.populateSelect($('#domesticConfigTable tbody').find('.mot:last'),transportTypes, 'CODE', 'NAME', '-select-',null, 1, true);

            // $('#domesticConfigTable tbody').find(".depDate:last").on('change', function () {
            //     var diff =  Math.floor(( Date.parse($('#domesticConfigTable tbody').find(".arrDate:last").val()) - Date.parse($('#domesticConfigTable tbody').find(".depDate:last").val()) ) / 86400000);
            //     $('#domesticConfigTable tbody').find(".noOfDays:last").val(diff + 1);
            // });
    
            // $('#domesticConfigTable tbody').find(".arrDate:last").on('change', function () {
            //     var diff =  Math.floor(( Date.parse($('#domesticConfigTable tbody').find(".arrDate:last").val()) - Date.parse($('#domesticConfigTable tbody').find(".depDate:last").val()) ) / 86400000);
            //     $('#domesticConfigTable tbody').find(".noOfDays:last").val(diff + 1);
            // });
            
        });

        $('.deatilAddBtnInternational').on('click', function () {
            var appendData = `
            <tr>
                <td><input class="dtlDelBtnInternational btn btn-danger" type="button" value="Del -" style="padding:3px;"></td>
                <td>
                                                <select name="erTypeI[]"  data-id="` + index +`" id="erTypeI_` + index +`" class="erTypeL form-control" style="width:13rem">
                                                    <option value="-1">Select ER Type</option>
                                                    <option value="EP">Employeee Paid</option>
                                                    <option value="CP">Company Paid</option>
                                                </select>
                                            </td>
                                            
                                            <td>
                                                <input type="text" name="locFrom[]"  data-id="` +index +`" id="locFrom_`+index+`"  class="locFrom form-control" style="width:10rem"> 
                                            </td>
                                            <td>
                                                <input type="text" name="arrDate[]"  data-id="`+index+`" id="arrDate_`+index+`" class="arrDate form-control" style="width:10rem"> 
                                            </td>
                                            <td>
                                                <input type="text" name="ticketNo[]" data-id="`+index+`" id="ticketNo_`+index+`" class="ticketNo form-control"> 
                                            </td>   
                                            <td>
                                                    <select name="expenseHead[]" data-id="`+index+`" id="expensehead_`+index+`" class="form-control">
                                                        <option value="-1">---select expense head---</option>
                                                        <option value="Accommodation">Accommodation</option>
                                                        <option value ="Airfare">Airfare</option>
                                                        <option value ="Auto Maintenance">Auto Maintenance</option>
                                                        <option value ="Airport Taxes and visas etc">Airport Taxes and visas etc</option>
                                                        <option value ="Black Berry (Monthly Usage Bill)">Black Berry (Monthly Usage Bill)</option>
                                                        <option value ="Books Newspaper & Subscription">Books Newspaper & Subscription</option>
                                                        <option value ="Cafeteria Expenses-admin">Cafeteria Expenses-admin</option>
                                                        <option value="Car hire">Car hire</option>
                                                        <option value ="Club Membership Fee">Club Membership Fee</option>
                                                        <option value ="Conveyance- Car Rental">Conveyance- Car Rental</option>
                                                        <option value ="Conveyance - Other">Conveyance - Other</option>
                                                        <option value ="Daily Allowance">Daily Allowance</option>
                                                        <option value ="Data Card">Data Card</option>
                                                        <option value="Expenses">Expenses</option>
                                                        <option value ="Entertainment">Entertainment</option>
                                                        <option value ="Expatriate Benefits">Expatriate Benefits</option>
                                                        <option value ="Expatriate CLA Expenses">Expatriate CLA Expenses</option>
                                                        <option value ="Gasoline Diesel Fuel Oil">Gasoline Diesel Fuel Oil</option>
                                                        <option value ="Guest House Expenses">Guest House Expenses</option>
                                                        <option value ="Hotel Meals (inclusive of Tips)">Hotel Meals (inclusive of Tips)</option>
                                                        <option value ="IS Information (others)">IS Information (others)</option>
                                                        <option value ="Lab Testing and Certification Cost">Lab Testing and Certification Cost</option>
                                                        <option value ="Laundry">Laundry</option>
                                                        <option value ="Lodging">Lodging</option>
                                                        <option value ="Meeting">Meeting</option>
                                                        <option value ="Miscellaneous Expenses">Miscellaneous Expenses</option>
                                                        <option value ="Mobile Handset Reimbursement">Mobile Handset Reimbursement</option>
                                                        <option value ="Mobile Phone Expenses">Mobile Phone Expenses</option>
                                                        <option value ="Other Tips">Other Tips</option>
                                                        <option value ="Postage & Courier Charge">Postage & Courier Charge</option>
                                                        <option value ="Reimb on Stamp Paper">Reimb on Stamp Paper</option>
                                                        <option value ="Relocation Expenses">Relocation Expenses</option>
                                                        <option value ="Stationary">Stationary</option>
                                                        <option value ="Supplies General">Supplies General</option>
                                                        <option value ="Taxi/Bus/Car rental (inc fuel & conv allow)">Taxi/Bus/Car rental (inc fuel & conv allow)</option>
                                                        <option value ="Telephone/Fax Expenses">Telephone/Fax Expenses</option>
                                                        <option value ="Train Fare/Bus Fare">Train Fare/Bus Fare</option>
                                                        <option value ="Ticket - Others">Ticket - Others</option>
                                                        <option value ="Toll Fees">Toll Fees</option>
                                                        <option value ="Training Expenses">Training Expenses</option>
                                                        <option value ="Uniforms and Towels -Admin">Uniforms and Towels -Admin</option>
                                            </select> 
                                            </td>
                                            <td>
                                                    <select class='currency form-control' name='currency[]' data-id="`+index+`" id="currency_`+index+`" >
                                                    </select>
                                            </td>   
                                            <td>
                                                <input type="number" name="exchangeRateInternational[]" data-id="`+index+`" id="exchangeInt_`+index+`"  class="exchangeRateInternational" step="any">       
                                            </td>
                                           
                                            <td>
                                                <input type="number" name="amountExp[]" step="any" data-id="`+index+`" id="amountExp_`+index+`" class="amountExpI">
                                            </td>
                                            <td>
                                                <textarea name="detRemarks[]" data-id="`+index+`" id="detRemarks_`+index+`"  class="detRemarks form-control" style="width:15rem ;padding:2rem"></textarea>
                                            </td>

            </tr>
            `;
            $('#internationalConfigTable tbody').append(appendData);
            all_data=document.currencyList;
            app.populateSelect($('#currency_'+index), all_data, 'code', 'code', '-select-',null, 1, true);
            $('#erTypeI_'+index).prop('required',true);
            $('#locFrom_'+index).prop('required',true);
            $('#arrDate_'+index).prop('required',true);
            $('#expensehead_'+index).prop('required',true);
            $('#amountExp_'+index).prop('required',true);       
              index += 1;          
            // app.addComboTimePicker(
            //         $('#internationalConfigTable tbody').find('.depTimeInternational:last'),
            //         $('#internationalConfigTable tbody').find('.arrTimeInternational:last')
            //         );
            
            app.addDatePicker(
                    $('#internationalConfigTable tbody').find('.depDateInternational:last'),
                    $('#internationalConfigTable tbody').find('.arrDate:last')
                    );
             
            app.populateSelect($('#internationalConfigTable tbody').find('.motInternational:last'), internationalPlaces, 'CODE', 'NAME', '-select-',null, 1, true);
            // app.populateSelect($('#internationalConfigTable tbody').find('.currency'), all_data, 'code', 'code', '-select-',null, 1, true);
            
            // $('#internationalConfigTable tbody').find(".depDateInternational:last").on('change', function () {
            //     var diff =  Math.floor(( Date.parse($('#internationalConfigTable tbody').find(".arrDateInternational:last").val()) - Date.parse($('#internationalConfigTable tbody').find(".depDateInternational:last").val()) ) / 86400000);
            //     $('#internationalConfigTable tbody').find(".noOfDaysInternational:last").val(diff + 1);
            // });
    
            // $('#internationalConfigTable tbody').find(".arrDateInternational:last").on('change', function () {
            //     var diff =  Math.floor(( Date.parse($('#internationalConfigTable tbody').find(".arrDateInternational:last").val()) - Date.parse($('#internationalConfigTable tbody').find(".depDateInternational:last").val()) ) / 86400000);
            //     $('#internationalConfigTable tbody').find(".noOfDaysInternational:last").val(diff + 1);
            // });
            
        });

        $('#domesticConfigTable').on('click', '.dtlDelBtn', function () {
            var selectedtr = $(this).parent().parent();
            selectedtr.remove();
            var test = 0;
            $(".amountExpL").each(function(){
                var t2 = $(this).val();
                conversion =  $(this).closest("tr").find("td:eq(6) input[type='number']").val();
                mul = eval(t2) * eval(conversion);
                test = eval(test + mul)
               });
           $('#totalAmountExp').val(test);
        });

        $('#internationalConfigTable').on('click', '.dtlDelBtnInternational', function () {
            var selectedtr = $(this).parent().parent();
            selectedtr.remove();
            var test = 0;
            var conversion = 1;
             var mul = 1;
            $(".amountExpI").each(function(){
                var t2 = $(this).val();
                conversion =  $(this).closest("tr").find("td:eq(7) input[type='number']").val();
                mul = eval(t2) * eval(conversion);
                test = eval(test + mul)
               });
           $('#totalAmountExpI').val(test);
        });

        // $('#addDocument').on('click', function () {
        //     $('#documentUploadModel').modal('show');
        // });

        // $('#uploadCancelBtn').on('click', function () {
        //     $('#documentUploadModel').modal('hide');
        // });

        // $('#uploadSubmitBtn').on('click', function () {
        //     if (myDropzone.files.length == 0) {
        //         $('#uploadErr').show();
        //         return;
        //     } else {
        //         $('#uploadErr').hide();
        //     }
        //     $('#documentUploadModel').modal('hide');
        //     myDropzone.processQueue();
        // });

        // var myDropzone;
        // Dropzone.autoDiscover = false;
        
        // myDropzone = new Dropzone("div#dropZoneContainer", {
        //     url: document.uploadUrl,
        //     autoProcessQueue: false,
        //     maxFiles: 1,
        //     addRemoveLinks: true,
        //     init: function () {
        //         this.on("success", function (file, success) {
        //             if (success.success) {
        //                 imageUpload(success.data);
        //                 app.showMessage("Upload successfull", 'success');
        //             }
        //             else{
        //                 app.showMessage("File type error", 'error');
        //             }
        //         });
        //         this.on("complete", function (file) {
        //             this.removeAllFiles(true);
        //         });
        //     }
        // });

        // var imageUpload = function (data) {
        //     window.app.pullDataById(document.pushDCFileLink, {
        //         'filePath': data.fileName,
        //         'fileName': data.oldFileName
        //     }).then(function (success) {
        //         if (success.success) {
        //             $('#fileDetailsTbl').append('<tr>'
        //                     +'<input type="hidden" name="fileUploadList[]" value="' + success.data.FILE_ID + '"><td>' + success.data.FILE_NAME + '</td>'
        //                     + '<td><a target="blank" href="' + document.basePath + '/uploads/travel_documents/' + success.data.FILE_IN_DIR_NAME + '"><i class="fa fa-download"></i></a></td>'
        //                     + '<td><button type="button" class="btn btn-danger deleteFile">DELETE</button></td></tr>');

        //         }
        //     }, function (failure) {
        //     });
        // }

        // $('#fileDetailsTbl').on('click', '.deleteFile', function () {  
        //     var selectedtr = $(this).parent().parent();
        //     selectedtr.remove();
        //     var rowCount1 = document.getElementById('fileDetailsTbl').rows.length;

        // });
    });
    app.addDatePicker($('.arrDate'));
    $('#expenseTypeTravel').change(function() {
        $('#travelIdsforall').empty();
        $('#addtraveldetails').empty();
        var exp = $(this).val();
        if (exp == 'ITR') {
            $('#travelIdsforall').append(`
                <div id="travelIdsforall" class="col-md-4" >
                                    
                                <label for="employeeCode">From Date:</label> 
                                <input type="text" id="fromDate" name="fromDate" class="form-control">
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">To Date:</label> 
                                <input type="text" id="toDate" name="toDate" class="form-control">
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">Departure:</label> 
                                <input type="text" name="departure" id="departure" class="form-control">
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">Destination:</label> 
                                <input type="text" name="destination" id="destination" class="form-control" >
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">Purpose:</label> 
                                <input type="text" id="purpose" name="purpose" class="form-control" >
                    
                </div>
            `);
            var travelIdToInsert = $('#travelIdToInsert');
            app.addDatePicker($('#fromDate'));
            app.addDatePicker($('#toDate'));


            app.populateSelect(travelIdToInsert,document.destinationsI,'TRAVEL_ID','DESTINATION', 'Select Travel Destination','');

            // $('#travelIdsforall').change('#expenseTypeTravel',function() {
            //     var val = $('#travelIdToInsert').val();
            //     // console.log(val);
            //     if (val != "") {
            //         app.pullDataById(document.getTravelDetail, {
            //             'travelId' : val,
            //             'type': "ITR",
            //         }).then(function (response) {
            //             if (response.success) {
            //                 // console.log(response.data);
            //                 $('#addtraveldetails').empty();
            //                 $('#addtraveldetails').append(`
            //                     <div class="col-md-4" id="fromDate">
                                    
            //                                     <label for="employeeCode">From Date:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.FROM_DATE+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">To Date:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.TO_DATE+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">Departure:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.DEPARTURE+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">Destination:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.DESTINATION+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">Requested Amount:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.REQUESTED_AMOUNT+`" disabled="disabled">
                                    
            //                     </div>
            //                 `);
            //             } else {
            //                 console.log('error');
            //             }
            //         }, function (error) {
            //             app.showMessage(error, 'error');
            //         });
            //     }
            // });
            // adding exepndse table
            $('#domesticConfigTable').hide();
            $('#ertyprL1').prop('required',false);
            $('#locFromL1').prop('required',false);
            $('#arrDateL1').prop('required',false);
            $('#expenseHeadL1').prop('required',false);
            $('#amountExpL1').prop('required',false);
            $('#expensehead_0').prop('required',true);
            $('#arrDate_0').prop('required',true);
            $('#locFrom_0').prop('required',true);
            $('#erTypeI_0').prop('required',true);
            $('#amountExp_0').prop('required',true);
            $('#domesticConfigTable tbody tr:not(:first)').remove();
            $('#domesticConfigTable tbody tr td input').val('');
            $('#domesticConfigTable tbody tr td select').val('-1');
            $('#internationalConfigTable').show();
        } else if(exp == 'LTR') {
            // console.log(document.destinationsL);
            $('#travelIdsforall').append(`
                <div id="travelIdsforall" class="col-md-4" >
                                    
                <label for="employeeCode">From Date:</label> 
                <input type="text" id="fromDate"  name="fromDate" class="form-control">
    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">To Date:</label> 
                                <input type="text" id="toDate" name="toDate" class="form-control">
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">Departure:</label> 
                                <input type="text" id="departure" name="departure"  class="form-control">
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">Destination:</label> 
                                <input type="text" id="destination" name="destination" class="form-control" >
                    
                </div>
                <div id="travelIdsforall" class="col-md-4">
                    
                                <label for="employeeCode">Purpose:</label> 
                                <input type="text" id="purpose" name="purpose" class="form-control" >
                    
                </div>
            </div>
            `);
            var travelIdToInsert = $('#travelIdToInsert');
            app.addDatePicker($('#fromDate'));
            app.addDatePicker($('#toDate'));

            app.populateSelect(travelIdToInsert,document.destinationsL,'TRAVEL_ID','DESTINATION', 'Select Travel Destination','');
            
            // getting data of this selected travel
            // $('#travelIdsforall').change('#expenseTypeTravel',function() {
            //     var val = $('#travelIdToInsert').val();
            //     // console.log(val);
            //     if (val != "") {
            //         app.pullDataById(document.getTravelDetail, {
            //             'travelId' : val,
            //             'type': "LTR",
            //         }).then(function (response) {
            //             if (response.success) {
            //                 // console.log(response.data);
            //                 $('#addtraveldetails').empty();
            //                 $('#addtraveldetails').append(`
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">From Date:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.FROM_DATE+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">To Date:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.TO_DATE+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">Departure:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.DEPARTURE+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">Destination:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.DESTINATION+`" disabled="disabled">
                                    
            //                     </div>
            //                     <div class="col-md-4">
                                    
            //                                     <label for="employeeCode">Requested Amount:</label> 
            //                                     <input type="text" class="form-control" value = "`+response.data.REQUESTED_AMOUNT+`" disabled="disabled">
                                    
            //                     </div>
            //                 `);
                            
            //             } else {
            //                 console.log('error');
            //             }
            //         }, function (error) {
            //             app.showMessage(error, 'error');
            //         });
            //     }
            // });
            $('#domesticConfigTable').show();
            $('#fromDate').prop('required',true);
            $('#toDate').prop('required',true);
            $('#departure').prop('required',true);
            $('#destination').prop('required',true);
            $('#domesticLocal').text('Domestic Travel');
            $('#amountExp').prop('required',false);
            $('#arrDate1').prop('required',false);
            $('#locFrom1').prop('required',false);
            $('#erTypeI1').prop('required',false);
            $('#expensehead').prop('required',false);
            $('#ertyprL_0').prop('required',true);
            $('#locFromL_0').prop('required',true);
            $('#arrDateL_0').prop('required',true);
            $('#expenseHead_0').prop('required',true);
            $('#amountExpL_0').prop('required',true);
            $('#conversionRateL_0').val('1');
            $('#currencyL_0').val('NPR');

            $('#internationalConfigTable').hide();
            $('#internationalConfigTable tbody tr:not(:first)').remove();
            $('#internationalConfigTable tbody tr td input').val('');
            $('#internationalConfigTable tbody tr td select').val('-1');
        }else{
            $('#travelIdsforall').empty();
            $('#travelIdsforall').append(`
            <div class="col-sm-4">
                <div class="form-group">
                <label>Purpose</label>
                    <input type = "text" class= "form-control" name = "purpose">
                </div>               
            </div>
            

            `);
            $('#domesticConfigTable').show();
            $('#domesticLocal').text('Local Expense');
            $('#internationalConfigTable').hide();
            $('#internationalConfigTable tbody tr:not(:first)').remove();
            $('#internationalConfigTable tbody tr td input').val('');
            $('#internationalConfigTable tbody tr td select').val('-1');

            $('#amountExp').prop('required',false);
            $('#arrDate1').prop('required',false);
            $('#locFrom1').prop('required',false);
            $('#erTypeI1').prop('required',false);
            $('#expensehead').prop('required',false);
            $('#ertyprL_0').prop('required',true);
            $('#locFromL_0').prop('required',true);
            $('#arrDateL_0').prop('required',true);
            $('#expenseHead_0').prop('required',true);
            $('#amountExpL_0').prop('required',true);
            $('#conversionRateL_0').val('1');
            $('#currencyL_0').val('NPR');
           
            app.addDatePicker(
                $('#domesticConfigTable tbody').find('.fromDateD:last'),
                $('#domesticConfigTable tbody').find('.arrDate:last')
                );
        }
    });
   
})(window.jQuery, window.app);


