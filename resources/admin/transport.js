// @ts-check

const { createAdminConfigRestClient } = require('../core/rest-client');

/**
 * @typedef {{
 *   success: boolean,
 *   data: Record<string, unknown>
 * }} AdminConfigResponse
 */

/**
 * @param {{
 *   getConfig: () => Record<string, unknown>,
 *   getData: (element: Element, key: string) => string|null
 * }} options
 */
const createAdminConfigTransport = ({ getConfig, getData }) => {
	/**
	 * @returns {Record<string, unknown>}
	 */
	const cfg = () => getConfig() || {};
	const restClient = createAdminConfigRestClient({ getConfig: cfg });

	/**
	 * Maps transport endpoint names to the REST routes registered by
	 * RestEndpoints. Unknown endpoints pass through unchanged.
	 *
	 * @type {Record<string, string>}
	 */
	const REST_ROUTE_FOR_ENDPOINT = {
		save: 'values',
		reset: 'reset',
		import: 'import',
		export: 'export',
		'data-source': 'data-source',
	};

	/**
	 * @param {HTMLFormElement|null} form
	 * @param {string} endpoint
	 * @returns {string}
	 */
	const restActionPath = (form, endpoint) => {
		const schemaId = form ? getData(form, 'schema-id') : '';
		const normalizedEndpoint = String(endpoint || '').replace(/^\/+|\/+$/g, '');
		const routeEndpoint = REST_ROUTE_FOR_ENDPOINT[normalizedEndpoint] || normalizedEndpoint;

		return schemaId && routeEndpoint ? `schemas/${schemaId}/${routeEndpoint}` : '';
	};

	return {
		hasRestTransport: () => restClient.hasTransport(),
		requestRest: restClient.request,
		restActionPath,
	};
};

module.exports = {
	createAdminConfigTransport,
};
