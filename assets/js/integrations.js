import {showSpinner, updateSpinnerText} from "./helper"; //getSelectedPostType, 
import axios from "axios";

export default function updateMetadataMapping() {
	return new Promise(
		async (resolve, reject) => {
			try {
				showSpinner();
				updateSpinnerText('Updating metadata mapping...');
				var map = {};
				const allInputs = document.querySelectorAll("input[data-integration='acf']");
				allInputs.forEach((input) => {
					map[input.name ] = input.value;
				});

				const userMap = document.querySelector('input[name="acf-user-map"]:checked')?.value;
				await saveMetadataMapping(map, userMap);
				resolve();
			} catch (error) {
				updateSpinnerText('Error while updating metadata mapping. Please try again.');
				reject(error);
			}
		},);
}

/**
 * Update integration post type in database
 *
 * @param postType
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
async function saveMetadataMapping(map, userMap) {
	const { rest_url, nonce } = window.PCCAdmin;
	return await axios.put(`${rest_url}/integrations/metadata`, {
		metadataMap: JSON.stringify(map),
		userMap: userMap,
	}, {
		headers: { 'X-WP-Nonce': nonce }
	});
}
