import axios from "axios";

const { rest_url, nonce } = window.PCC_BOOTSTRAP ?? {};

export const apiClient = axios.create({
  baseURL: rest_url,
  withCredentials: true,
  headers: {
    "X-WP-Nonce": nonce,
  },
});
