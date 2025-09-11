import { Button } from "@pantheon-systems/pds-toolkit-react";

export default function CollectionReady() {
  return (
    <div className="w-full h-[80vh] flex flex-col items-center justify-center text-center">
      <img
        src={`${window.PCC_BOOTSTRAP.assets_url}/images/rocket-launch.png`}
        alt="Collection ready"
        className="w-40 h-40 mb-6"
      />
      <h3 className="pds-ts-2xl font-bold mb-6">Your collection is ready!</h3>
      <p className="mb-12">
        You&apos;re now ready to start publishing using Content Publisher.
      </p>
      <a href={window.PCC_BOOTSTRAP.plugin_main_page}>
        <Button label="Go to your collection" />
      </a>
    </div>
  );
}
