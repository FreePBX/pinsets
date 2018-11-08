var theForm = document.edit;
theForm.description.focus();

function edit_onsubmit() {

	defaultEmptyOK = false;

	if (!isAlphanumeric(theForm.description.value))
		return warnInvalid(theForm.description, _("Please enter a valid Description"));

	if($.inArray(theForm.description.value, description) != -1)
		return warnInvalid($('input[name=description]'),  sprintf(_("%s already used, please use a different description."),theForm.description.value));

	return true;
}
