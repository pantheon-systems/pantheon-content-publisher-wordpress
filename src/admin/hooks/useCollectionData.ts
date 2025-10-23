export function useCollectionData() {
  const {
    configured: { collection_url, collection_id, collection_data },
  } = window.CPUB_BOOTSTRAP;

  const collectionName = (() => {
    if (collection_data?.name) {
      return collection_data.name;
    }
    try {
      return new URL(collection_url).host;
    } catch {
      return collection_url;
    }
  })();

  const collectionUrl = (() => {
    if (collection_data?.url) {
      return collection_data.url;
    }
    return collection_url;
  })();

  return {
    collectionName,
    collectionUrl,
    collectionId: collection_id,
  };
}
