
(function ($, app) {
    $(document).ready(function () {
        document.searchManager = {
            company: [],
            branch: [],
            department: [],
            designation: [],
            serviceType: [],
            serviceEventType: [],
            employee: [],
            gender: [],
            employeeType: [],
            location: [],
            functionalType: [],
            companyListener: null,
            branchListener: null,
            departmentListener: null,
            designationListener: null,
            positionListener: null,
            serviceTypeListener: null,
            serviceEventTypeListener: null,
            employeeListener: null,
            genderListener: null,
            employeeTypeListener: null,
            locationListener: null,
            functionalTypeListener: null,
            ids: []
            , setCompany: function (company) {
                this.company = company;
            }, getCompany: function () {
                return this.company;
            }, setBranch: function (branch) {
                this.branch = branch;
            }, getBranch: function () {
                return this.company
            }, setDepartment: function (department) {
                this.department = department;
            }, getDepartment: function () {
                return this.department
            }, setDesignation: function (designation) {
                this.designation = designation;
            }, getDesignation: function () {
                return this.designation;
            }, setServiceType: function (servicetype) {
                this.serviceType = servicetype;
            }, getServiceType: function () {
                return this.serviceType
            }, setServiceEventType: function (serviceEventType) {
                this.serviceEventType = serviceEventType;
            }, getServiceEventType: function () {
                return this.serviceEventType
            }, setEmployee: function (employee) {
                this.employee = employee;
            }, getEmployee: function () {
                return this.employee
            }, setGender: function (gender) {
                this.gender = gender;
            }, getGender: function () {
                return this.gender;
            }, setEmployeeType: function (employeeType) {
                this.employeeType = employeeType;
            }, getEmployeeType: function () {
                return this.employeeType;
            }, setLocation: function (location) {
                this.location = location;
            }, getLocation: function () {
                return this.location;
            }, setfunctionalType: function (functionalType) {
                this.location = functionalType;
            }, getfunctionalType: function () {
                return this.functionalType;
            }, getEmployeeById: function (id) {
                var filteredList = this.employee.filter(function (item) {
                    return item['EMPLOYEE_ID'] == id;
                });
                return filteredList[0];
            }, setCompanyListener: function (listener) {
                this.companyListener = listener;
            }, setBranchListener: function (listener) {
                this.branchListener = listener
            }, setDepartmentListener: function (listener) {
                this.departmentListener = listener;
            }, setDesignationListener: function (listener) {
                this.designationListener = listener;
            }, setServiceTypeListener: function (listener) {
                this.serviceTypeListener = listener;
            }, setServiceEventTypeListener: function (listener) {
                this.serviceEventTypeListener = listener;
            }, setEmployeeListener: function (listener) {
                this.employeeListener = listener;
            }, setGenderListener: function (listener) {
                this.genderListener = listener;
            }, setEmployeeTypeListener: function (listener) {
                this.employeeTypeListener = listener;
            }, setLocationListener: function (listener) {
                this.locationListener = listener;
            }, setfunctionalTypeListener: function (listener) {
                this.functionalTypeListener = listener;
            }, callCompanyListener: function () {
                if (this.companyListener !== null) {
                    this.companyListener();
                }
            }, callBranchListener: function () {
                if (this.branchListener !== null) {
                    this.branchListener();
                }
            }, callDepartmentListener: function () {
                if (this.departmentListener !== null) {
                    this.departmentListener();
                }
            }, callDesignationListener: function () {
                if (this.designationListener !== null) {
                    this.designationListener();
                }
            }, callPositionListener: function () {
                if (this.positionListener !== null) {
                    this.positionListener();
                }
            }, callServiceTypeListener: function () {
                if (this.serviceTypeListener !== null) {
                    this.serviceTypeListener();
                }
            }, callServiceEventTypeListener: function () {
                if (this.serviceEventTypeListener !== null) {
                    this.serviceEventTypeListener();
                }
            }, callEmployeeListener: function () {
                if (this.employeeListener !== null) {
                    this.employeeListener();
                }
            }, callGenderListener: function () {
                if (this.genderListener !== null) {
                    this.genderListener();
                }
            }, callEmployeeTypeListener: function () {
                if (this.employeeTypeListener !== null) {
                    this.employeeTypeListener();
                }
            }, callLocationListener: function () {
                if (this.locationListener !== null) {
                    this.locationListener();
                }
            }, callfunctionalTypeListener: function () {
                if (this.functionalTypeListener !== null) {
                    this.functionalTypeListener();
                }
            },
            setIds: function (ids) {
                this.ids = ids;
            },
            getIds: function () {
                return this.ids;
            },
            getSearchValues: function () {
                var values = {};
                $.each(this.ids, function (key, value) {
                    if (typeof value !== "undefined") {
                        values[value] = $('#' + value).val();
                    }
                });
                return values;
            },
            reset: function () {
                let acl = document.acl;
                let aclControlVal='F';
                $.each(this.ids, function (key, value) {
                    $('#' + value).val(-1).change();
                });
                for(let i = 0; i < acl['CONTROL'].length; i++){
                    $.each(this.ids, function (key, value) {
//                    console.log(value);
                        let $company = $('#' + 'companyId');
                        let $branch = $('#' + 'branchId');
                        let $department = $('#' + 'departmentId');
                        let $designation = $('#' + 'designationId');
                        let $position = $('#' + 'positionId');
//                    let $serviceType = $('#' + 'serviceTypeId');
//                    let $serviceEventType = $('#' + 'serviceEventTypeId');
//                    let $employee = $('#' + 'employeeId');
//                    
                    let populateValues = [];
                    if (typeof acl !== 'undefined') {
                        aclControlVal = acl['CONTROL'];

                        $.each(acl['CONTROL_VALUES'], function (k, v) {
                            if (v.CONTROL == acl['CONTROL'][i]) {
                                populateValues.push(v.VAL);
                            }
                        });
//                console.log(acl['CONTROL']);
                    }  //end if
                        if (typeof value !== "undefined") {
                            if (value == 'companyId' || value == 'branchId' || value == 'designationId' || value == 'departmentId' || value == 'positionId') {
                                switch (aclControlVal[i]) {
                                    case 'F':
                                       // $('#' + value).val(-1).change();
                                        break;
                                    case 'C':
                                        if (value == 'companyId') {
                                            $company.val(populateValues);
                                            $company.trigger('change');
                                        } else {
                                           // $('#' + value).val(-1).change();
                                        }
                                        break;
                                    case 'B':
                                        if (value == 'branchId') {
                                            $branch.val(populateValues);
                                            $branch.trigger('change');
                                        } else {
                                           // $('#' + value).val(-1).change();
                                        }
                                        break;
                                    case 'DS':
                                        if (value == 'designationId') {
                                            $designation.val(populateValues);
                                            $designation.trigger('change');
                                        } else {
                                           // $('#' + value).val(-1).change();
                                        }
                                        break;
                                    case 'DP':
                                        if (value == 'departmentId') {
                                            $department.val(populateValues);
                                            $department.trigger('change');
                                        } else {
                                           // $('#' + value).val(-1).change();
                                        }
                                        break;
                                    case 'P':
                                        if (value == 'positionId') {
                                            $position.val(populateValues);
                                            $position.trigger('change');
                                        } else {
                                           // $('#' + value).val(-1).change();
                                        }
                                        break;
                                }
                            } else {
                                $('#' + value).val(-1).change();
                            }
                        }
                    
                    });
                }
                
                
                if (this.resetEvent !== null) {
                    this.resetEvent();
                }
            },
            resetEvent: null,
            registerResetEvent: function (fn) {
                this.resetEvent = fn;
            },
            setSearchValues: function (values) {
                $.each(this.ids, function (key, value) {
                    if (typeof values[value] !== "undefined") {
                        $('#' + value).val(values[value]).trigger('change.select2');
                    }
                });
            },
            getSelectedEmployee: function () {
                var selectedValues = $('#employeeId').val();
                var employeeList = this.getEmployee();
                if (selectedValues === null || selectedValues === "-1") {
                    return employeeList;
                }
                return employeeList.filter(function (item) {
                    if (Array.isArray(selectedValues)) {
                        if($.inArray(item['EMPLOYEE_ID'], selectedValues) >= 0){
                            return item;
                        };
                    } else {
                        return item['EMPLOYEE_ID'] == selectedValues;
                    }
                });
            }
        };
        (function () {
            $('.hris-reset-btn').on('click', function () {
                document.searchManager.reset();
                app.resetField();
            });
        })();

        /*
         * Search javascript code starts here
         */
        var changeSearchOption = function (companyId, branchId, departmentId, designationId, positionId, serviceTypeId, serviceEventTypeId, employeeId, genderId, employeeTypeId, locationId,functionalTypeId) {
            document.searchManager.setIds(JSON.parse(JSON.stringify(arguments)));

            var $company = $('#' + companyId);
            var $branch = $('#' + branchId);
            var $department = $('#' + departmentId);
            var $designation = $('#' + designationId);
            var $position = $('#' + positionId);
            var $serviceType = $('#' + serviceTypeId);
            var $serviceEventType = $('#' + serviceEventTypeId);
            var $employee = $('#' + employeeId);
            
            var $gender = $('#' + "random-random");
            var $employeeType = $('#' + "random-random");
            var $location = $('#' + "random-random");
            var $functionalType = $('#' + "random-random");
            if (typeof genderId !== 'undefined' && genderId !== null) {
                $gender = $('#' + genderId);
            }
            if (typeof employeeTypeId !== 'undefined' && employeeTypeId !== null) {
                $employeeType = $('#' + employeeTypeId);
            }
            if (typeof locationId !== 'undefined' && locationId !== null) {
                $location = $('#' + locationId);
            }
            if (typeof functionalTypeId !== 'undefined' && functionalTypeId !== null) {
                $functionalType = $('#' + functionalTypeId);
            }

            /* setup functions */
            var populateList = function ($element, list, id, value, defaultMessage, selectedId) {
                $element.html('');
                if (typeof defaultMessage !== "undefined" && !$element.prop('multiple')) {
                    $element.append($("<option></option>").val(-1).text(defaultMessage));
                }
                var concatArray = function (keyList, list, concatWith) {
                    var temp = '';
                    if (typeof concatWith === 'undefined') {
                        concatWith = ' ';
                    }
                    for (var i in keyList) {
                        var listValue = list[keyList[i]];
                        if (i == (keyList.length - 1)) {
                            temp = temp + ((listValue === null) ? '' : listValue);
                            continue;
                        }
                        temp = temp + ((listValue === null) ? '' : listValue) + concatWith;
                    }

                    return temp;
                };
                for (var i in list) {
                    var text = null;
                    if (typeof value === 'object') {
                        text = concatArray(value, list[i], ' ');
                    } else {
                        text = list[i][value];
                    }
                    if (typeof selectedId !== 'undefined' && selectedId != null && selectedId == list[i][id]) {
                        $element.append($("<option selected='selected'></option>").val(list[i][id]).text(text));
                    } else {
                        $element.append($("<option></option>").val(list[i][id]).text(text));
                    }
                }
            };
//            var search = function (list, where) {
//                return list.filter(function (item) {
//                    for (var i in where) {
//                        if (!(item[i] === where[i] || where[i] == -1)) {
//                            return false;
//                        }
//                    }
//                    return true;
//                });
//            };
            var search = function (list, where) {
                return list.filter(function (item) {
                    for (var i in where) {
                        var value = where[i];
                        if (Array.isArray(value)) {
                            var xc = false;
                            for (var x in value) {
                                if (item[i] === value[x]) {
                                    xc = true;
                                    break;
                                }
                            }
                            if(xc==false){
                                return xc;
                            }
                        } else if (value === null) {

                        } else {
                            if (!(item[i] === value || value == -1)) {
                                return false;
                            }
                        }

                    }
                    return true;
                });
            };
            var onChangeEvent = function ($element, fn) {
                $element.on('change', function () {
                    var $this = $(this);
                    fn($this);
                });
            };

            var employeeSearchAndPopulate = function () {
                var searchParams = {'COMPANY_ID': $company.val(), 'BRANCH_ID': $branch.val(), 'DEPARTMENT_ID': $department.val(), 'DESIGNATION_ID': $designation.val(), 'POSITION_ID': $position.val(), 'SERVICE_TYPE_ID': $serviceType.val(), 'SERVICE_EVENT_TYPE_ID': $serviceEventType.val()};
                if ($gender.length != 0) {
                    searchParams['GENDER_ID'] = $gender.val();
                }
                if ($employeeType.length != 0) {
                    searchParams['EMPLOYEE_TYPE'] = $employeeType.val();
                }
                if ($location.length != 0) {
                    searchParams['LOCATION_ID'] = $location.val();
                }
                if ($functionalType.length != 0) {
                    searchParams['FUNCTIONAL_TYPE_ID'] = $functionalType.val();
                }
                var employeeList = search(document.searchValues['employee'], searchParams);
                document.searchManager.setEmployee(employeeList);
                populateList($employee, employeeList, 'EMPLOYEE_ID', 'FULL_NAME', 'All Employee');
            };
            /* setup functions */

            /* initialize dropdowns */
            populateList($company, document.searchValues['company'], 'COMPANY_ID', 'COMPANY_NAME', 'All Company');
            populateList($branch, document.searchValues['branch'], 'BRANCH_ID', 'BRANCH_NAME', 'All Branch');
            populateList($department, document.searchValues['department'], 'DEPARTMENT_ID', 'DEPARTMENT_NAME', 'All Department');
            populateList($designation, document.searchValues['designation'], 'DESIGNATION_ID', 'DESIGNATION_TITLE', 'All Designation');
            populateList($position, document.searchValues['position'], 'POSITION_ID', 'POSITION_NAME', 'All Position');
            populateList($serviceType, document.searchValues['serviceType'], 'SERVICE_TYPE_ID', 'SERVICE_TYPE_NAME', 'All Service Type');
            populateList($serviceEventType, document.searchValues['serviceEventType'], 'SERVICE_EVENT_TYPE_ID', 'SERVICE_EVENT_TYPE_NAME', 'Working');
            populateList($employee, document.searchValues['employee'], 'EMPLOYEE_ID', 'FULL_NAME', 'All Employee');

            document.searchManager.setCompany(document.searchValues['company']);
            document.searchManager.setBranch(document.searchValues['branch']);
            document.searchManager.setDepartment(document.searchValues['department']);
            document.searchManager.setDesignation(document.searchValues['designation']);
            document.searchManager.setServiceType(document.searchValues['serviceType']);
            document.searchManager.setServiceEventType(document.searchValues['serviceEventType']);
            document.searchManager.setEmployee(document.searchValues['employee']);
            
            if ($gender.length != 0) {
                populateList($gender, document.searchValues['gender'], 'GENDER_ID', 'GENDER_NAME', 'All Gender');
            }
            if ($employeeType.length != 0) {
                populateList($employeeType, document.searchValues['employeeType'], 'EMPLOYEE_TYPE_KEY', 'EMPLOYEE_TYPE_VALUE', 'All Employee Type');
            }
            if ($location.length != 0) {
                populateList($location, document.searchValues['location'], 'LOCATION_ID', 'LOCATION_EDESC', 'All Location');
            }
            if ($functionalType.length != 0) {
                populateList($functionalType, document.searchValues['functionalType'], 'FUNCTIONAL_TYPE_ID', 'FUNCTIONAL_TYPE_EDESC', 'All Functional Type');
//                document.searchManager.setfunctionalType(document.searchValues['functionalType']);
            }
            /* initialize dropdowns */

            /* setup change events */
            onChangeEvent($company, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callCompanyListener();
            });

            onChangeEvent($branch, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callBranchListener();
            });

            onChangeEvent($department, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callDepartmentListener();
            });
            onChangeEvent($designation, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callDesignationListener();
            });
            onChangeEvent($position, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callPositionListener();
            });
            onChangeEvent($serviceType, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callServiceTypeListener();
            });
            onChangeEvent($serviceEventType, function ($this) {
                employeeSearchAndPopulate();
                document.searchManager.callServiceEventTypeListener();
            });
            onChangeEvent($employee, function ($this) {
                document.searchManager.callEmployeeListener();
            })

            if ($gender.length != 0) {
                onChangeEvent($gender, function ($this) {
                    employeeSearchAndPopulate();
                    document.searchManager.callGenderListener();
                });
            }
            if ($employeeType.length != 0) {
                onChangeEvent($employeeType, function ($this) {
                    employeeSearchAndPopulate();
                    document.searchManager.callEmployeeTypeListener();
                });
            }
            if ($location.length != 0) {
                onChangeEvent($location, function ($this) {
                    employeeSearchAndPopulate();
                    document.searchManager.callLocationListener();
                });
            }
            if ($functionalType.length != 0) {
                onChangeEvent($functionalType, function ($this) {
                    employeeSearchAndPopulate();
                    document.searchManager.callfunctionalTypeListener();
                });
            }
            var acl = document.acl;
            var employeeDetail = document.employeeDetail;
            if (typeof acl !== 'undefined' && typeof employeeDetail !== 'undefined') {

                for(let i = 0; i < acl['CONTROL'].length; i++){
                    var populateValues = [];
                    $.each(acl['CONTROL_VALUES'], function (k, v) {

                        if (v.CONTROL == acl['CONTROL'][i]) {
                            populateValues.push(v.VAL);
                        }
                    });
                    
                    switch (acl['CONTROL'][i]) {
                        case 'C':
                            $company.val((populateValues.length<1)?employeeDetail['COMPANY_ID']:populateValues);
                            $company.trigger('change');
                            $company.prop('disabled', true);
                            break;
                        case 'B':
                            $branch.val((populateValues.length<1)?employeeDetail['BRANCH_ID']:populateValues);
                            $branch.trigger('change');
                            $branch.prop('disabled', true);
                            break;
                        case 'DS':
                            $designation.val((populateValues.length<1)?employeeDetail['DESIGNATION_ID']:populateValues);
                            $designation.trigger('change');
                            $designation.prop('disabled', true);
                            break;
                        case 'DP':
                            $department.val((populateValues.length<1)?employeeDetail['DEPARTMENT_ID']:populateValues);
                            $department.trigger('change');
                            $department.prop('disabled', true);
                            break;
                        case 'P':
                            $position.val((populateValues.length<1)?employeeDetail['POSITION_ID']:populateValues);
                            $position.trigger('change');
                            $position.prop('disabled', true);
                            break;
                    }
                }
            }

        };

        if (typeof document.searchValues !== 'undefined') {
            changeSearchOption("companyId", "branchId", "departmentId", "designationId", "positionId", "serviceTypeId", "serviceEventTypeId", "employeeId", "genderId", "employeeTypeId", "locationId","functionalTypeId");
        } else {
            if (typeof document.getSearchDataLink !== "undefined") {
                app.serverRequest(document.getSearchDataLink, {}).then(function (response) {
                    document.searchValues = response.data;
                    changeSearchOption("companyId", "branchId", "departmentId", "designationId", "positionId", "serviceTypeId", "serviceEventTypeId", "employeeId", "genderId", "employeeTypeId", "locationId","functionalTypeId");
                });
            } else {
                throw "No data or url set."
            }
        }


        /* setup change events */

    });
})(window.jQuery, window.app);