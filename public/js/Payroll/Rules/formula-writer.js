window.formulaWriter = function (id, data) {
    var replaceAll = function (rule, val, newVal) {
        if (rule.indexOf(val) >= 0) {
            rule = rule.replace(val, newVal);
            return replaceAll(rule, val, newVal);
        } else {
            return rule;
        }
    };

    var convertToCodeForm = function (input) {
        var output = replaceAll(input, " ", "_");
        return output.toUpperCase();
    };

    var list = [];

    var monthlyValueList = data.monthlyValueList;
    var flatValueList = data.flatValueList;
    var variableList = data.variableList;
    var systemRuleList = data.systemRuleList;
    var referencingRuleList = data.referencingRuleList;
    var referencingRuleListOthers=data.referencingRuleListOthers;

    for (var i in monthlyValueList) {
        list.push(`[M:${convertToCodeForm(monthlyValueList[i]['MTH_EDESC'])}]`);
    }
    for (var i in flatValueList) {
        list.push(`[F:${convertToCodeForm(flatValueList[i]['FLAT_EDESC'])}]`);
    }
    for (var i in variableList) {
        list.push(`[V:${variableList[i]}]`);
    }
    for (var i in systemRuleList) {
        list.push(`[S:${systemRuleList[i]}]`);
    }
    for (var i in referencingRuleList) {
        list.push(`[R:${convertToCodeForm(referencingRuleList[i]['PAY_EDESC'])}]`);
    }
    
    for (var i in referencingRuleListOthers){
         list.push(`[PM:${convertToCodeForm(referencingRuleListOthers[i]['PAY_EDESC'])}]`);
         list.push(`[PS:${convertToCodeForm(referencingRuleListOthers[i]['PAY_EDESC'])}]`);
		 list.push(`[MS:${convertToCodeForm(referencingRuleListOthers[i]['PAY_EDESC'])}]`);
    }

    CodeMirror.registerHelper("hint", "customword", function (editor, options) {
        var cur = editor.getCursor();
        var curLine = editor.getLine(cur.line);
        var end = cur.ch;
        var start = end;

        while (true) {
            if (start - 1 < 0) {
                break;
            }
            var charBefore = curLine.substring(start - 1, start);
            if (charBefore == " ") {
                break;
            }
            start--;
        }

        var substr = curLine.substring(start, end);
        var filteredList = [];
        if (substr == "") {
            filteredList = list;
        } else {
            filteredList = list.filter(function (item) {
                return item.indexOf(substr) >= 0;
            });

        }


        return {list: filteredList, from: CodeMirror.Pos(cur.line, start), to: CodeMirror.Pos(cur.line, end)};
    });
    CodeMirror.commands.autocomplete = function (cm) {
        cm.showHint({hint: CodeMirror.hint.customword});
    }
    var editor = CodeMirror.fromTextArea(document.getElementById(id), {
        lineNumbers: true,
        extraKeys: {"Ctrl-Space": "autocomplete"}
    });
    return editor;
};
