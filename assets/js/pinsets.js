var theForm = document.edit;
theForm.description.focus();

function edit_onsubmit() {

	defaultEmptyOK = false;

	if (!isAlphanumeric(theForm.description.value))
		return warnInvalid(theForm.description, _("Please enter a valid Description"));

	return true;
}