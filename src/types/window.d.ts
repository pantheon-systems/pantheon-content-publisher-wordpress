interface PostTypeOption {
  name: string;
  label: string;
}

interface Window {
  CPUB_BOOTSTRAP: {
    rest_url: string;
    nonce: string;
    site_url: string;
    assets_url: string;
    plugin_main_page: string;
    is_pcc_configured: boolean;
    available_post_types: PostTypeOption[];
    configured: {
      collection_url: string;
      collection_id: string;
      publish_as: string;
      webhook?: {
        url: string;
        notice_dismissed: boolean;
      };
      collection_data?: {
        id: string;
        url: string;
        name: string;
      };
    };
  };
}
