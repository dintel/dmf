function msg(type, text) {
    if(type == "error")
        type = "danger";
    $.bootstrapGrowl(text,{'type':type,delay:3000,width:'auto',align:'center'});
}

function onlyUnique(value, index, self) {
    return self.indexOf(value) === index;
}

Array.prototype.diff = function(a) {
    return this.filter(function(i) {return a.indexOf(i) < 0;});
};

function focusDefaultControl(e) {
    var modal = $(e.target);
    var controls = $(":input[data-default-control]",modal);
    if(controls.length > 0) {
        controls.filter(":first").focus();
    } else {
        $(":input:eq(1)",modal).focus();
    }
}

function confirmModal(e) {
    if(e.which == 10 || e.which == 13) {
        var modal = $(this).parents(".modal:first");
        var confirmButton = $("button[data-confirm-button]",modal);
        confirmButton.click();
    }
}

$(document).ready(function(){
    $(".modal").on("shown.bs.modal",focusDefaultControl);
    $(".modal").on("keyup",":input",confirmModal);
});
