(function ($, app) {
    'use strict';
    angular.module("hris", ['ui.bootstrap'])
            .controller('qualificationController', function ($scope, $uibModal, $log, $document, $window) {

                //for qualification detail [add and delete]
                $scope.degreeList = [];
                $scope.universityList = [];
                $scope.programList = [];
                $scope.courseList = [];
                $scope.employeeQualificationList = [];
                $scope.rankType = [
                    {"id": "GPA", "name": "GPA"},
                    {"id": "PER", "name": "Percentage"},
                ];
                var employeeId = parseInt(angular.element(document.getElementById('employeeId')).val());
                $scope.qualificationFormList = [];
                window.app.pullDataById(document.pullAcademicDetailLink, {
                    'employeeId': employeeId
                }).then(function (success) {
                    $scope.$apply(function () {
                        var data = success.data;
                        $scope.counter = '';
                        $scope.degreeList = data.degreeList;
                        $scope.universityList = data.universityList;
                        $scope.programList = data.programList;
                        $scope.courseList = data.courseList;
                        $scope.employeeQualificationList = data.employeeQualificationList;
                        var num = data.num;
                        if (num == 0) {
                            $scope.counter = 1;
                            $scope.qualificationFormList.push({
                                id: 0,
                                academicDegreeId: $scope.degreeList[0],
                                academicUniversityId: $scope.universityList[0],
                                academicProgramId: $scope.programList[0],
                                academicCourseId: $scope.courseList[0],
                                rankType: $scope.rankType[0],
                                rankValue: 0,
                                passedYr: "",
                                checkbox: "checkboxq0",
                                checked: false
                            });
                        } else {
                            $scope.counter = num;
                            for (var j = 0; j < num; j++) {
                                if (data.employeeQualificationList[j].rankType == 'GPA') {
                                    var rankType = $scope.rankType[0];
                                } else if (data.employeeQualificationList[j].rankType == 'PER') {
                                    var rankType = $scope.rankType[1];
                                }

                                $scope.qualificationFormList.push({
                                    id: data.employeeQualificationList[j].id,
                                    academicDegreeId: data.employeeQualificationList[j].degreeDtl,
                                    academicUniversityId: data.employeeQualificationList[j].universityDtl,
                                    academicProgramId: data.employeeQualificationList[j].programDtl,
                                    academicCourseId: data.employeeQualificationList[j].courseDtl,
                                    rankType: rankType,
                                    rankValue: data.employeeQualificationList[j].rankValue,
                                    passedYr: data.employeeQualificationList[j].passedYr,
                                    checkbox: "checkboxq" + j,
                                    checked: false
                                });
                            }
                        }
                        $scope.view = function () {
                            $scope.qualificationFormList.push({
                                id: 0,
                                academicDegreeId: $scope.degreeList[0],
                                academicUniversityId: $scope.universityList[0],
                                academicProgramId: $scope.programList[0],
                                academicCourseId: $scope.courseList[0],
                                rankType: $scope.rankType[0],
                                rankValue: 0,
                                passedYr: "",
                                checkbox: "checkboxq" + $scope.counter,
                                checked: false
                            });
                            $scope.counter++;
                            $("select").select2();
                        };
                        $scope.addQualification = function () {
                            var qualificationRecord = $scope.qualificationFormList;
                            console.log(qualificationRecord);
                            for (var l = 0; l < $scope.qualificationFormList.length; l++) {
                                var rankValue = $scope.qualificationFormList[l].rankValue;
                                var passedYr = $scope.qualificationFormList[l].passedYr;
                                console.log(rankValue, passedYr);
                            }

                            window.app.pullDataById(document.submitQualificationDtlLink, {
                                "qualificationRecord": qualificationRecord,
                                'employeeId': employeeId,
                                "qualificationRecordNum": qualificationRecord.length
                            }).then(function (success) {
                                $scope.$apply(function () {
                                    console.log(success.data);
                                });
                            }, function (failure) {
                                console.log(failure);
                            });
                        };
                        $scope.delete = function () {
                            var tempC = 0;
                            var length = $scope.qualificationFormList.length;
                            for (var i = 0; i < length; i++) {
                                if ($scope.qualificationFormList[i - tempC].checked) {
                                    var id = $scope.qualificationFormList[i - tempC].id;
                                    if (id != 0) {
                                        window.app.pullDataById(document.deleteQualificationDtlLink, {
                                            "id": id
                                        }).then(function (success) {
                                            $scope.$apply(function () {
                                                console.log(success.data);
                                            });
                                        }, function (failure) {
                                            console.log(failure);
                                        });
                                    }
                                    $scope.qualificationFormList.splice(i - tempC, 1);
                                    tempC++;
                                }
                            }
                        }
                    });
                }, function (failure) {
                    console.log(failure);
                });
                $scope.upload = function () {

                };
                //for document including both image as well as file upload and remove
                $scope.profilePictureId = document.profilePictureId;
                $scope.fileTypes = document.fileTypes;
                $scope.file = {
                    fileCode: null,
                    fileTypeCode: null,
                    filePath: null,
                    editMode: false,
                    fileName: null
                };
                var fileTypeKeys = Object.keys($scope.fileTypes);
                $scope.file.fileTypeCode = (fileTypeKeys.length > 0) ? fileTypeKeys[0] : null;
                $scope.dropZone;
                $scope.noImageSelected = false;
                Dropzone.options.myAwesomeDropzone = {
                    maxFiles: 1,
                    acceptedFiles: 'image/*',
                    autoProcessQueue: false,
                    addRemoveLinks: true,
                    init: function () {
                        $scope.$apply(function () {
                            $scope.dropZone = this;
                        }.bind(this));
                        this.on("success", function (file, success) {
                            console.log("Upload Image Response ", success);
                            $scope.$apply(function () {
                                if (success.success) {
                                    $scope.file.filePath = success.data.fileName;
                                    $scope.file.fileName = success.data.oldFileName;
                                    $scope.noImageSelected = false;
                                    $scope.imageUploadResponseSuccess();
                                }
                            });
                        });
                    }
                };
                if ($scope.profilePictureId != -1) {
                    window.app.pullDataById(document.pullEmployeeFileLink, {
                        'employeeFileId': $scope.profilePictureId
                    }).then(function (success) {
                        $scope.$apply(function () {
                            if (success.data != null) {
                                $scope.file.fileCode = success.data['FILE_CODE'];
                                $scope.file.fileTypeCode = success.data['FILETYPE_CODE'];
                                $scope.file.filePath = success.data['FILE_PATH'];
                            }
                        });
                    }, function (failure) {
                        console.log("pullEmployeeFile failure", failure);
                    });
                }

                $scope.viewImage = function (image) {
                    return document.basePath + "/uploads/" + image;
                };
                $scope.post = function () {
                    if ($scope.dropZone.files.length == 0) {
                        $scope.noImageSelected = true;
                        return;
                    }
                    $scope.dropZone.processQueue();
                };

                $scope.imageUploadResponseSuccess = function () {
                    window.app.pullDataById(document.pushEmployeeProfileLink, {
                        'fileCode': $scope.file.fileCode,
                        'fileTypeCode': $scope.file.fileTypeCode,
                        'filePath': $scope.file.filePath,
                        'fileName': $scope.file.fileName,
                        'employeeId': document.employeeId
                    }).then(function (success) {
                        $scope.$apply(function () {
                            if (success.data != null) {
                                $scope.file.editMode = false;
                                $scope.file.fileCode = success.data.fileCode
                            }
                            window.app.successMessage("Profile Image set successfully");
                        });
                    }, function (failure) {
                    });

                };
                $scope.edit = function () {
                    $scope.file.editMode = true;
                    $scope.dropZone.removeAllFiles();
                };
                $scope.employeeDocuments = [];
                $scope.drop = function (fileCode, key) {
                    window.app.pullDataById(document.dropEmployeeFileLink, {
                        'fileCode': fileCode
                    }).then(function (success) {
                        $scope.$apply(function () {
                            $scope.employeeDocuments.splice(key, 1);
                        });
                    }, function (failure) {
                    });
                };
                $scope.addDocument = function () {
                    var modalInstance = $uibModal.open({
                        ariaLabelledBy: 'modal-title',
                        ariaDescribedBy: 'modal-body',
                        templateUrl: 'myModalContent.html',
                        resolve: {
                            fileTypes: function () {
                                return $scope.fileTypes;
                            }
                        },
                        controller: function ($scope, $uibModalInstance, fileTypes) {
                            $scope.ok = function () {
                                if (document.myDropzone.files.length == 0) {
                                    $scope.valid = false;
                                    return;
                                }
                                document.fileTypeCode = $scope.fileTypeCode;
                                document.fileTypeName = $scope.fileTypeName;
                                document.myDropzone.processQueue();
                            };
                            $scope.cancel = function () {
                                $uibModalInstance.dismiss('cancel');
                            };
                            $scope.valid = true;
                            $scope.fileTypes = fileTypes;
                            $scope.fileTypeName = '';
                            $scope.fileTypeCode = Object.keys($scope.fileTypes)[0];
                        }
                    });
                    console.log("modalInstance", modalInstance);
                    modalInstance.rendered.then(function () {
                        document.myDropzone = new Dropzone("#dropZoneContainer", {
                            url: document.restfulUrl,
                            autoProcessQueue: false,
                            maxFiles: 1,
                            addRemoveLinks: true
                        });
                        document.myDropzone.on("success", function (file, success) {
                            console.log("File Upload Response", success);
                            $scope.$apply(function () {
                                var uploadResponse = success.data;
                                var toInsertFileName = uploadResponse.oldFileName;
                                if (document.fileTypeName != '') {
                                    toInsertFileName = document.fileTypeName;
                                }
                                modalInstance.close({
                                    fileTypeCode: document.fileTypeCode,
                                    fileName: uploadResponse.fileName,
                                    oldFileName: toInsertFileName
                                });
                            });
                        });
                    });
                    modalInstance.result.then(function (selectedItem) {
                        window.app.pullDataById(document.pushEmployeeDocumentLink, {
                            'fileTypeCode': selectedItem.fileTypeCode,
                            'filePath': selectedItem.fileName,
                            'oldFileName': selectedItem.oldFileName,
                            'employeeId': document.employeeId
                        }).then(function (success) {
                            $scope.$apply(function () {
                                if (success.data != null) {
                                    var fileTypeDesc = $scope.fileTypes[selectedItem.fileTypeCode];
                                    $scope.employeeDocuments.push({
                                        FILE_CODE: success.data.fileCode,
                                        FILE_PATH: selectedItem.fileName,
                                        FILE_NAME: selectedItem.oldFileName,
                                        FILETYPE_CODE: selectedItem.fileTypeCode,
                                        FILE_TYPE: fileTypeDesc
                                    });
                                }
                            });
                        }, function (failure) {
                        });
                    }, function () {
                        console.log("Modal Action Cancelled");
                    });
                };
                window.app.pullDataById(document.pullEmployeeFileByEmpIdLink, {
                    'employeeId': document.employeeId
                }).then(function (success) {
                    $scope.$apply(function () {
                        $scope.employeeDocuments = success.data;
                        console.log($scope.employeeDocuments);
                    });
                }, function (failure) {
                });



                // for employee experience [add and delete function]
                $scope.organizationType = [
                    {"id": "Financial", "name": "Financial"},
                    {"id": "Non-Financial", "name": "Non-Financial"},
                ];
                $scope.experienceFormList = [];
                $scope.counterExperience = '';
                $scope.experienceFormTemplate = {
                    id: 0,
                    organizationTypeId: $scope.organizationType[0],
                    organizationName: "",
                    position: "",
                    fromDate: "",
                    toDate: "",
                    checkbox: "checkboxe0",
                    checked: false
                };
                $scope.addDatePicker = function (fromId, toId) {
                    app.startEndDatePicker(fromId, toId);
                }
                if (employeeId !== 0) {
                    window.app.pullDataById(document.pullExperienceDetailLink, {
                        'employeeId': employeeId
                    }).then(function (success) {
                        $scope.$apply(function () {
                            var experienceList = success.data;
                            var experienceNum = success.num;
                            console.log(experienceList);
                            if (experienceNum > 0) {
                                $scope.counterExperience = experienceNum;
                                for (var j = 0; j < experienceNum; j++) {
                                    if (experienceList[j].ORGANIZATION_TYPE == 'Financial') {
                                        var organizationType = $scope.organizationType[0];
                                    } else if (experienceList[j].ORGANIZATION_TYPE == 'Non-Financial') {
                                        var organizationType = $scope.organizationType[1];
                                    }

                                    $scope.experienceFormList.push(angular.copy({
                                        id: experienceList[j].ID,
                                        organizationTypeId: organizationType,
                                        organizationName: experienceList[j].ORGANIZATION_NAME,
                                        position: experienceList[j].POSITION,
                                        fromDate: experienceList[j].FROM_DATE,
                                        toDate: experienceList[j].TO_DATE,
                                        checkbox: "checkboxe" + j,
                                        checked: false
                                    }));
                                    // $scope.addDatePicker('expfromDate_checkboxe'+j, 'exptoDate_checkboxe'+j);
                                }
                            } else {
                                $scope.counterExperience = 1;
                                $scope.experienceFormList.push(angular.copy($scope.experienceFormTemplate));
                                //$scope.$apply(function () {
//                                    $scope.addDatePicker('expfromDate_checkboxe0', 'exptoDate_checkboxe0');
                                // });
                            }
                        });
                    }, function (failure) {
                        console.log(failure);
                    });
                } else {
                    $scope.counterExperience = 1;
                    $scope.experienceFormList.push(angular.copy($scope.experienceFormTemplate));
//                    $scope.$apply(function () {
//                        app.startEndDatePicker('expfromDate_checkboxe0', 'exptoDate_checkboxe0');
//                    });
                }

                $scope.addExperience = function () {
                    $scope.experienceFormList.push(angular.copy({
                        id: 0,
                        organizationTypeId: $scope.organizationType[0],
                        organizationName: "",
                        position: "",
                        fromDate: "",
                        toDate: "",
                        checkbox: "checkboxe" + $scope.counterExperience,
                        checked: false
                    }));
                    $scope.counterExperience++;
//                    $scope.$apply(function () {
//                        $("select").select2();
//                        app.startEndDatePicker('expfromDate_checkboxe'+$scope.counterExperience, 'exptoDate_checkboxe'+$scope.counterExperience);
//                    });
                };
                $scope.deleteExperience = function () {
                    var tempE = 0;
                    var lengthE = $scope.experienceFormList.length;
                    for (var i = 0; i < lengthE; i++) {
                        if ($scope.experienceFormList[i - tempE].checked) {
                            var id = $scope.experienceFormList[i - tempE].id;
                            if (id != 0) {
                                window.app.pullDataById(document.deleteExperienceDtlLink, {
                                    "id": id
                                }).then(function (success) {
                                    $scope.$apply(function () {
                                        console.log(success.data);
                                    });
                                }, function (failure) {
                                    console.log(failure);
                                });
                            }
                            $scope.experienceFormList.splice(i - tempE, 1);
                            tempE++;
                        }
                    }
                }
                $scope.submitExperience = function () {
                    if ($scope.employeeExperienceForm.$valid && $scope.experienceFormList.length > 0) {
                        console.log("hellow");
                        $scope.experienceListEmpty = 1;
                        if (($scope.experienceFormList.length == 1 && angular.equals($scope.experienceFormTemplate, $scope.experienceFormList[0])) || $scope.experienceFormList.length == 0) {
                            console.log("app log", "The form is not filled");
                            $scope.experienceListEmpty = 0;
                        }
                        console.log($scope.experienceFormList);
                        window.app.pullDataById(document.submitExperienceDtlLink, {
                            experienceList: $scope.experienceFormList,
                            employeeId: parseInt(employeeId),
                            experienceListEmpty: parseInt($scope.experienceListEmpty)
                        }).then(function (success) {
                            $scope.$apply(function () {
                                console.log(success.data);
                                $window.location.href = document.urlSubmitExperience;
                            });
                        }, function (failure) {
                            console.log(failure);
                        });
                    } else if ($scope.experienceFormList.length == 0) {
                        $window.location.href = document.urlSubmitExperience;
                    }
                }


                // for employee training [add and delete function]
                $scope.trainingFormList = [];
                $scope.counterTraining = '';
                $scope.trainingFormTemplate = {
                    id: 0,
                    trainingName: "",
                    description: "",
                    fromDate: "",
                    toDate: "",
                    checkbox: "checkboxt0",
                    checked: false
                };
                if (employeeId !== 0) {
                    window.app.pullDataById(document.pullTrainingDetailLink, {
                        'employeeId': employeeId
                    }).then(function (success) {
                        $scope.$apply(function () {
                            var trainingList = success.data;
                            var trainingNum = success.num;
                            console.log(trainingList);
                            if (trainingNum > 0) {
                                $scope.counterTraining = trainingNum;
                                for (var j = 0; j < trainingNum; j++) {
                                    $scope.trainingFormList.push(angular.copy({
                                        id: trainingList[j].ID,
                                        trainingName: trainingList[j].TRAINING_NAME,
                                        description: trainingList[j].DESCRIPTION,
                                        fromDate: trainingList[j].FROM_DATE,
                                        toDate: trainingList[j].TO_DATE,
                                        checkbox: "checkboxt" + j,
                                        checked: false
                                    }));
                                }
                            } else {
                                $scope.counterTraining = 1;
                                $scope.trainingFormList.push(angular.copy($scope.trainingFormTemplate));
                            }
                        });
                    }, function (failure) {
                        console.log(failure);
                    });
                } else {
                    $scope.counterTraining = 1;
                    $scope.trainingFormList.push(angular.copy($scope.trainingFormTemplate));
                }

                $scope.addTraining = function () {
                    $scope.trainingFormList.push(angular.copy({
                        id: 0,
                        trainingName: "",
                        description: "",
                        fromDate: "",
                        toDate: "",
                        checkbox: "checkboxt" + $scope.counterTraining,
                        checked: false
                    }));
                    $scope.counterTraining++;
                    $("select").select2();
                };
                $scope.deleteTraining = function () {
                    var tempT = 0;
                    var lengthT = $scope.trainingFormList.length;
                    for (var i = 0; i < lengthT; i++) {
                        if ($scope.trainingFormList[i - tempT].checked) {
                            var id = $scope.trainingFormList[i - tempT].id;
                            if (id != 0) {
                                window.app.pullDataById(document.deleteTrainingDtlLink, {
                                    "id": parseInt(id)
                                }).then(function (success) {
                                    $scope.$apply(function () {
                                        console.log(success.data);
                                    });
                                }, function (failure) {
                                    console.log(failure);
                                });
                            }
                            $scope.trainingFormList.splice(i - tempT, 1);
                            tempT++;
                        }
                    }
                }
                $scope.submitTraining = function () {
                    if ($scope.employeeTrainingForm.$valid && $scope.trainingFormList.length > 0) {
                        $scope.trainingListEmpty = 1;
                        if (($scope.trainingFormList.length == 1 && angular.equals($scope.trainingFormTemplate, $scope.trainingFormList[0])) || $scope.trainingFormList.length == 0) {
                            console.log("app log", "The form is not filled");
                            $scope.trainingListEmpty = 0;
                        }
                        window.app.pullDataById(document.submitTrainingDtlLink, {
                            trainingList: $scope.trainingFormList,
                            employeeId: parseInt(employeeId),
                            trainingListEmpty: parseInt($scope.trainingListEmpty)
                        }).then(function (success) {
                            $scope.$apply(function () {
                                console.log(success.data);
                                $window.location.href = document.urlSubmitTraining;
//                                $window.localStorage.setItem("msg","Employee Profile Successfully Submitted!!!");
                            });
                        }, function (failure) {
                            console.log(failure);
                        });
                    } else if ($scope.trainingFormList.length == 0) {
                        $window.location.href = document.urlSubmitTraining;
                    }
                }


                // employee relation starts from here
                $scope.ConditionalType = [
                    {"id": "N", "name": "No"},
                    {"id": "Y", "name": "Yes"},
                ];
                $scope.relationFormList = [];
                $scope.counterRelation = '';
                $scope.relationList = document.relation;
                $scope.relationFormTemplate = {
                    eRId: 0,
                    relationId: $scope.relationList[0],
                    personName: "",
                    dob: "",
                    isDependent: $scope.ConditionalType[0],
                    isNominee: $scope.ConditionalType[0],
                    checkbox: "checkboxr0",
                    checked: false
                };

                if (employeeId !== 0) {
                    window.app.pullDataById(document.pullReltionDetailLink, {
                        'employeeId': employeeId
                    }).then(function (success) {
                        $scope.$apply(function () {
                            var relationList = success.data;
                            var relationNum = success.num;
                            if (relationNum > 0) {
                                $scope.counterRelation = relationNum;
                                for (var j = 0; j < relationNum; j++) {
                                    let tempIndex = $scope.relationList.findIndex(record => record.RELATION_ID === relationList[j].RELATION_ID);

                                    $scope.relationFormList.push(angular.copy({
                                        eRId: relationList[j].E_R_ID,
                                        relationId: $scope.relationList[tempIndex],
                                        personName: relationList[j].PERSON_NAME,
                                        dob: relationList[j].DOB,
                                        isDependent: $scope.ConditionalType[relationList[j].IS_DEPENDENT_DET],
                                        isNominee: $scope.ConditionalType[relationList[j].IS_NOMINEE_DET],
                                        checkbox: "checkboxr"+ j,
                                        checked: false
                                    }));

                                }
                            } else {
                                $scope.counterRelation = 1;
                                $scope.relationFormList.push(angular.copy($scope.relationFormTemplate));
                            }
                        });
                    }, function (failure) {
                        console.log(failure);
                    });
                } else {
                    $scope.counterRelation = 1;
                    $scope.relationFormList.push(angular.copy($scope.experienceFormTemplate));
                }





                $scope.submitRelation = function () {
                    console.log('submit realtion');
                    if ($scope.employeeRelationForm.$valid && $scope.relationFormList.length > 0) {
                        $scope.relationListEmpty = 1;


                        if (($scope.relationFormList.length == 1 && angular.equals($scope.relationFormTemplate, $scope.relationFormList[0])) || $scope.relationFormList.length == 0) {
                            console.log("app log", "The form is not filled");
                            $scope.relationListEmpty = 0;
                        }
                        window.app.pullDataById(document.submitRelationDtlLink, {
                            relationList: $scope.relationFormList,
                            employeeId: parseInt(employeeId),
                            relationListEmpty: parseInt($scope.relationListEmpty)
                        }).then(function (success) {
                            $scope.$apply(function () {
                                console.log(success.data);
                                $window.location.href = document.urlSubmitRelation;
                            });
                        }, function (failure) {
                            console.log(failure);
                        });
                    } else if ($scope.employeeRelationForm.length == 0) {
                        console.log('no val');
                        $window.location.href = document.urlSubmitRelation;
                    }

                };

                $scope.addRelation = function () {
                    $scope.relationFormList.push(angular.copy({
                        id: 0,
                        relationId: $scope.relationList[0],
                        personName: "",
                        dob: "",
                        isDependent: $scope.ConditionalType[0],
                        isNominee: $scope.ConditionalType[0],
                        checkbox: "checkboxr" + $scope.counterRelation,
                        checked: false
                    }));
                    $scope.counterRelation++;
//                    $scope.$apply(function () {
//                        $("select").select2();
//                        app.startEndDatePicker('expfromDate_checkboxe'+$scope.counterExperience, 'exptoDate_checkboxe'+$scope.counterExperience);
//                    });
                };


                $scope.deleteRelation = function () {
                    console.log('sdfsdf');
                    var tempE = 0;
                    var lengthE = $scope.relationFormList.length;
                    for (var i = 0; i < lengthE; i++) {
                        if ($scope.relationFormList[i - tempE].checked) {
                            var id = $scope.relationFormList[i - tempE].eRId;
                            console.log(id);
                            if (id != 0) {
                                window.app.pullDataById(document.deleteRelationDtlLink, {
                                    "id": id
                                }).then(function (success) {
                                    $scope.$apply(function () {
                                        console.log(success.data);
                                    });
                                }, function (failure) {
                                    console.log(failure);
                                });
                            }
                            $scope.relationFormList.splice(i - tempE, 1);
                            tempE++;
                        }
                    }
                }







            })
            .directive("datepicker", function () {
                return {
                    restrict: "A",
                    require: "ngModel",
                    link: function (scope, elem, attrs, ngModelCtrl) {
                        $(elem).val(attrs.dvalue);
                        app.addDatePicker($(elem));
                    }
                }
            }).directive("select2", function () {
        return {
            restrict: "A",
            require: "ngModel",
            link: function (scope, elem, attrs, ngModelCtrl) {
                $(elem).select2();
            }
        }
    });
})(window.jQuery, window.app);

