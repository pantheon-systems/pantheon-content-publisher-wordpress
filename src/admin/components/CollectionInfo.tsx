import { Button, LinkNewWindow } from "@pantheon-systems/pds-toolkit-react";

interface CollectionInfoProps {
  collectionUrl: string;
  collectionId: string;
  onDisconnect: () => void;
}

export default function CollectionInfo({
  collectionUrl,
  collectionId,
  onDisconnect,
}: CollectionInfoProps) {
  return (
    <div className="flex items-center justify-between bg-[#F4F4F4] p-5 rounded">
      <div className="flex items-center gap-12">
        <div>
          <p className="pds-ts-s uppercase text-pds-color-text-default-secondary font-bold">
            Collection URL
          </p>
          <LinkNewWindow url={collectionUrl}>{collectionUrl}</LinkNewWindow>
        </div>
        <div>
          <p className="pds-ts-s uppercase text-pds-color-text-default-secondary font-bold">
            Collection ID
          </p>
          <span>{collectionId}</span>
        </div>
      </div>
      <Button
        label="Disconnect collection"
        variant="secondary"
        onClick={onDisconnect}
      />
    </div>
  );
}
