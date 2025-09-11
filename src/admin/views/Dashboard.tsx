import {
  Button,
  LinkNewWindow,
  RadioGroup,
  SectionMessage,
  Modal,
  useToast,
  ToastType,
} from "@pantheon-systems/pds-toolkit-react";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { useMutation } from "@tanstack/react-query";
import { apiClient } from "../api/client";
import { getErrorMessage } from "../utils/errors";

export default function Dashboard() {
  const {
    configured: { collection_url, collection_id, publish_as },
  } = window.PCC_BOOTSTRAP;

  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [addToast] = useToast();

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

  const onSubmit = (values: { publishAs: "post" | "page" }) => {
    updateMutation.mutate(values.publishAs);
  };

  const collectionName = (() => {
    if (window.PCC_BOOTSTRAP.configured.collection_data?.name) {
      return window.PCC_BOOTSTRAP.configured.collection_data.name;
    }
    try {
      return new URL(collection_url).host;
    } catch {
      return collection_url;
    }
  })();

  const collectionUrl = (() => {
    if (window.PCC_BOOTSTRAP.configured.collection_data?.url) {
      return window.PCC_BOOTSTRAP.configured.collection_data.url;
    }
    return collection_url;
  })();

  return (
    <div className="space-y-6">
      <h2 className="pds-ts-2xl">{collectionName}</h2>

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
            <span>{collection_id}</span>
          </div>
        </div>
        <Button label="Disconnect collection" variant="secondary" />
      </div>

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
            onClick={() => setShowConfirmModal(true)}
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
                onClick={() => setShowConfirmModal(false)}
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
    </div>
  );
}
