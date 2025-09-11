interface Window {
  PCC_BOOTSTRAP: {
    rest_url: string;
    nonce: string;
    site_url: string;
    assets_url: string;
    plugin_main_page: string;
    is_pcc_configured: boolean;
    configured: {
      collection_url: string;
      collection_id: string;
      publish_as: "post" | "page";
      collection_data?: {
        id: string;
        url: string;
        name: string;
      };
    };
  };
}
