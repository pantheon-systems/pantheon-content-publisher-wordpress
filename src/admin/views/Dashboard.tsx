import {
  Button,
  RadioGroup,
  SectionMessage,
  Modal,
  useToast,
  ToastType,
  TextInput,
  ButtonLink,
  ClipboardButton,
} from "@pantheon-systems/pds-toolkit-react";
import { useMemo, useState } from "react";
import { useForm, Controller } from "react-hook-form";
import { useMutation } from "@tanstack/react-query";
import { apiClient } from "../api/client";
import { getErrorMessage } from "../lib/errors";
import { SRC_ACTIONS } from "../lib/constants";
import { useCollectionData } from "../hooks/useCollectionData";
import CollectionInfo from "../components/CollectionInfo";

export default function Dashboard() {
  const { publish_as, webhook } = window.PCC_BOOTSTRAP.configured;
  const { collectionName, collectionUrl, collectionId } = useCollectionData();

  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [showDisconnectModal, setShowDisconnectModal] = useState(false);
  const [disconnectConfirmText, setDisconnectConfirmText] = useState("");
  const [addToast] = useToast();
  const [showDismissConfirmModal, setShowDismissConfirmModal] = useState(false);
  const [hideWebhookNotice, setHideWebhookNotice] = useState(false);

  const shouldShowWebhookNotice = useMemo(() => {
    // Show when not dismissed server-side AND we have a valid webhook url
    return (
      Boolean(webhook?.url) &&
      webhook?.notice_dismissed === false &&
      !hideWebhookNotice
    );
  }, [webhook, hideWebhookNotice]);

  const dismissNoticeMutation = useMutation({
    mutationFn: async () =>
      apiClient.put("/webhook-notice", { dismissed: true }),
    onSuccess: () => {
      // Optimistically hide without extra fetch
      if (window.PCC_BOOTSTRAP.configured.webhook) {
        window.PCC_BOOTSTRAP.configured.webhook.notice_dismissed = true;
      }
      setShowDismissConfirmModal(false);
    },
    onMutate: () => {
      setHideWebhookNotice(true);
    },
    onError: () => {
      setHideWebhookNotice(false);
      addToast(
        ToastType.Critical,
        "Failed to hide the notice. Please try again."
      );
    },
  });

  const {
    control,
    handleSubmit,
    formState: { isDirty },
    reset,
    getValues,
  } = useForm<{
    publishAs: "post" | "page";
  }>({
    mode: "onChange",
    defaultValues: {
      publishAs: publish_as ?? "post",
    },
  });

  const updateMutation = useMutation({
    mutationFn: async (publishAs: "post" | "page") => {
      return apiClient.put("/collection", {
        post_type: publishAs,
      });
    },
    onSuccess: () => {
      setShowConfirmModal(false);
      addToast(ToastType.Success, "Changes saved successfully");
      reset({
        publishAs: getValues("publishAs"),
      });
    },
    onError: () => {
      setShowConfirmModal(false);
    },
  });

  const disconnectMutation = useMutation({
    mutationFn: async () => {
      return apiClient.delete("/disconnect");
    },
    onSuccess: () => {
      setShowDisconnectModal(false);
      const baseUrl = window.PCC_BOOTSTRAP.plugin_main_page;
      const separator = baseUrl.includes("?") ? "&" : "?";
      window.location.href = `${baseUrl}${separator}src=${SRC_ACTIONS.DISCONNECTED}`;
    },
    onError: () => {
      addToast(
        ToastType.Critical,
        "Failed to disconnect collection. Please try again or contact support if the issue persists."
      );
    },
  });

  const onSubmit = (values: { publishAs: "post" | "page" }) => {
    updateMutation.mutate(values.publishAs);
  };

  const handleDisconnect = () => {
    setShowDisconnectModal(true);
  };

  const handleConfirmDisconnect = () => {
    disconnectMutation.mutate();
  };

  const isConfirmEnabled =
    disconnectConfirmText.trim().toUpperCase() === "DISCONNECT";

  return (
    <div className="space-y-6">
      <h2 className="pds-ts-2xl">{collectionName}</h2>

      {shouldShowWebhookNotice && (
        <div className="p-6 rounded bg-[#E5E0F8] flex gap-4">
          <div>
            <img
              src={`${window.PCC_BOOTSTRAP.assets_url}/images/webhooks.png`}
              alt="Webhook notice"
              className="max-h-[240px] md:block hidden object-contain"
            />
          </div>
          <div className="flex flex-col gap-4 w-full">
            <div>
              <p className="pds-ts-xl font-bold mb-1">
                Complete your setup by configuring webhooks for this collection
              </p>
              <p>
                Keep your site content automatically updated when this
                collection changes.
                <br />
                <strong>
                  Note: Webhook secrets are not supported at this time. Please
                  do not set one in the ‘Secret’ field.
                </strong>
              </p>
              <p>
                Copy the webhook URL below and add it to your Content Publisher
                dashboard.
              </p>
            </div>
            <div>
              <div className="bg-[#F4F4F4] p-2 px-4 rounded flex gap-2 items-center justify-between">
                <span className="break-all bg-none pds-ts-m font-mono text-[#3B3D45]">
                  {webhook?.url}
                </span>
                <ClipboardButton
                  ariaLabel="Copy to clipboard"
                  clipboardText={webhook?.url}
                />
              </div>
            </div>
            <div className="pds-button-group mt-4">
              <ButtonLink
                displayType="icon-end"
                iconName="externalLink"
                linkContent={
                  <a
                    href={`https://content.pantheon.io/dashboard/collections/${collectionId}?tabId=settings#webhooks`}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Go to webhook settings
                  </a>
                }
              />
              <Button
                label="Dismiss"
                variant="subtle"
                onClick={() => {
                  setShowDismissConfirmModal(true);
                }}
                isLoading={dismissNoticeMutation.isPending}
              />
            </div>
          </div>
        </div>
      )}

      <Modal
        modalIsOpen={showDismissConfirmModal}
        setModalIsOpen={setShowDismissConfirmModal}
        title="Hide this notice?"
        size="md"
      >
        <div className="space-y-6">
          <SectionMessage
            type="warning"
            message="If webhooks are not configured, your site will not stay in sync with this collection. You should only hide this notice if you have already configured the webhooks in Content Publisher."
          />
          <p>
            This notice will not be shown again until this collection is
            disconnected.
          </p>
          <div className="pds-modal__button-group">
            <Button
              type="button"
              label="Cancel"
              variant="secondary"
              disabled={dismissNoticeMutation.isPending}
              onClick={() => {
                setShowDismissConfirmModal(false);
              }}
            />
            <Button
              type="button"
              label="Yes, don’t show again"
              variant="critical"
              onClick={() => {
                setShowDismissConfirmModal(false);
                dismissNoticeMutation.mutate();
              }}
              isLoading={dismissNoticeMutation.isPending}
              disabled={dismissNoticeMutation.isPending}
            />
          </div>
        </div>
      </Modal>

      <CollectionInfo
        collectionUrl={collectionUrl}
        collectionId={collectionId}
        onDisconnect={handleDisconnect}
      />

      <form onSubmit={handleSubmit(onSubmit)}>
        {updateMutation.error && (
          <SectionMessage
            type="critical"
            message={getErrorMessage(
              updateMutation.error,
              "Failed to save configuration. Please try again."
            )}
            className="mb-4"
          />
        )}

        <div className="max-w-xl mb-5">
          <Controller
            name="publishAs"
            control={control}
            render={({ field }) => (
              <RadioGroup
                id="publish-as"
                label="Publish your document as:"
                options={[
                  { label: "Post", value: "post" },
                  { label: "Page", value: "page" },
                ]}
                value={field.value}
                onValueChange={field.onChange}
                onBlur={field.onBlur}
              />
            )}
          />
        </div>

        <SectionMessage
          type="info"
          message="You can find Content Publisher documents under the 'Posts' or 'Pages' menu in WordPress, depending on your selection at the time of publishing."
        />

        <div className="pds-button-group mt-4">
          <Button
            label="Save configuration"
            type="button"
            disabled={!isDirty || updateMutation.isPending}
            onClick={() => {
              setShowConfirmModal(true);
            }}
          />
        </div>

        <Modal
          modalIsOpen={showConfirmModal}
          setModalIsOpen={setShowConfirmModal}
          title="Save configuration?"
          size="sm"
        >
          <div className="space-y-8">
            <p>
              Existing documents in this collection will have to be republished
              to appear on your site under this post type.
            </p>
            <div className="pds-modal__button-group">
              <Button
                type="button"
                label="Cancel"
                variant="secondary"
                disabled={updateMutation.isPending}
                onClick={() => {
                  setShowConfirmModal(false);
                }}
              />
              <Button
                type="submit"
                label="Save configuration"
                isLoading={updateMutation.isPending}
                disabled={updateMutation.isPending}
              />
            </div>
          </div>
        </Modal>
      </form>

      <Modal
        modalIsOpen={showDisconnectModal}
        setModalIsOpen={setShowDisconnectModal}
        title={`Disconnect this collection?`}
        size="md"
      >
        <div className="space-y-8">
          <SectionMessage
            type="warning"
            message="This action is permanent and can’t be undone."
          />

          <div className="space-y-4">
            <p className="pds-ts-m font-bold">
              Disconnecting {collectionName} will:
            </p>
            <p>
              Disconnect all Google Docs in the collection from your site and
              prevent you from updating site content through Google Docs.
            </p>
            <p>
              The content will remain on the site, manageable using the
              WordPress admin interface.
            </p>
          </div>

          <div className="space-y-2">
            <TextInput
              id="confirm-disconnect"
              label="Type “DISCONNECT” to proceed"
              value={disconnectConfirmText}
              onChange={(e) => setDisconnectConfirmText(e.target.value)}
              disabled={disconnectMutation.isPending}
            />
          </div>

          <div className="pds-modal__button-group">
            <Button
              type="button"
              label="Cancel"
              variant="secondary"
              disabled={disconnectMutation.isPending}
              onClick={() => setShowDisconnectModal(false)}
            />
            <Button
              type="button"
              label="Disconnect"
              variant="critical"
              onClick={() => handleConfirmDisconnect()}
              isLoading={disconnectMutation.isPending}
              disabled={!isConfirmEnabled || disconnectMutation.isPending}
            />
          </div>
        </div>
      </Modal>
    </div>
  );
}
