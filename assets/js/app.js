import {deleteConfigDetails, PccDisconnect, redirectToMainPage} from "./helper";
import createSite from "./createSite";
import {hideErrorMessage, hideSpinner, showErrorMessage, showSpinner, updateSpinnerText} from "./helper";
import updatePostType from "./updatePostType";
import authenticate from "./authenticate";

if (document.getElementById('pcc-app-authenticate') != undefined) {
	document.getElementById('pcc-app-authenticate').addEventListener('click', async function () {
		try {
			showSpinner();
			await authenticate();
			redirectToMainPage();
		} catch (error) {
			showErrorMessage(`Error while saving management token: ${error.message}`)
			hideSpinner();
		}
	});
}

if (document.getElementById('pcc-create-site') != undefined) {
	document.getElementById('pcc-create-site').addEventListener('click', async function (e) {
		try {
			showSpinner();
			await createSite();
			redirectToMainPage();
		} catch (error) {
			showErrorMessage([
				`Error while creating site: ${error.response.data}`,
				'Your management token might be restricted or you might have to tried to authenticate with a gmail.com account'
			], true)
			hideSpinner();
		}
	});
}

if (document.getElementById('pcc-update-collection') != undefined) {
	document.getElementById('pcc-update-collection').addEventListener('click', async function () {
		try {
			await updatePostType();
			redirectToMainPage();
		} catch (error) {
			showErrorMessage(`Error while creating site: ${error.response.data}`)
		} finally {
		}
	});
}

if (document.getElementById('pcc-disconnect') != undefined) {
	document.getElementById('pcc-disconnect').addEventListener('click', PccDisconnect);
}

if (document.getElementById('pcc-error-close-button') != undefined) {
	document.getElementById('pcc-error-close-button').addEventListener('click', function () {
		hideErrorMessage();
	});
}
