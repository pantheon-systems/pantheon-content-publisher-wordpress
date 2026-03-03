import {
  Button,
  Select,
  SectionMessage,
  TextInput,
} from "@pantheon-systems/pds-toolkit-react";
import { useMutation } from "@tanstack/react-query";
import { Controller, useForm } from "react-hook-form";
import { apiClient } from "../../../api/client";
import { getErrorMessage } from "../../../lib/errors";
import { usePostTypeOptions } from "../../../hooks/usePostTypeOptions";

interface Props {
  onDone: () => void;
  onCancel: () => void;
  onLoadingChange: (loading: boolean) => void;
  onLoadingStepChange: (step: string) => void;
}

export default function StepCreateCollection({
  onDone,
  onCancel,
  onLoadingChange,
  onLoadingStepChange,
}: Props) {
  const postTypeOptions = usePostTypeOptions();

  const {
    control,
    handleSubmit,
    formState: { isValid },
  } = useForm<{
    collectionUrl: string;
    publishAs: string;
  }>({
    mode: "onChange",
    defaultValues: {
      collectionUrl: window.CPUB_BOOTSTRAP.site_url,
      publishAs: "post",
    },
  });

  const createCollectionFlow = useMutation({
    mutationFn: async (type: string) => {
      onLoadingStepChange("Creating your collection...");
      const siteResp = await apiClient.post<string>("/site");
      const siteId = siteResp.data;

      if (!siteId) {
        throw new Error("Failed to create collection. Please try again.");
      }

      onLoadingStepChange("Creating an access token for your collection...");
      await apiClient.post("/api-key");

      onLoadingStepChange("Registering webhooks for your collection...");
      await apiClient.put("/webhook");

      onLoadingStepChange("Finalizing setup...");
      await apiClient.post("/collection", {
        site_id: siteId,
        post_type: type,
      });
    },
    onMutate: () => {
      onLoadingChange(true);
    },
    onSuccess: onDone,
    onError: () => {
      onLoadingChange(false);
    },
  });

  const onSubmit = (data: { collectionUrl: string; publishAs: string }) => {
    if (createCollectionFlow.isPending) return;
    createCollectionFlow.mutate(data.publishAs);
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      {createCollectionFlow.error && (
        <SectionMessage
          type="critical"
          message={getErrorMessage(
            createCollectionFlow.error,
            "Failed to create collection. Please try again."
          )}
          className="mb-4"
        />
      )}

      <div className="mt-4 flex gap-4 justify-between">
        <div className="w-full">
          <Controller
            name="collectionUrl"
            control={control}
            rules={{ required: true }}
            render={({ field }) => (
              <TextInput
                id="collection-url"
                label="Collection URL"
                showLabel={false}
                className="max-w-xl"
                placeholder={`${window.CPUB_BOOTSTRAP.site_url}`}
                value={field.value}
                onChange={(e) => field.onChange(e.target.value)}
                onBlur={field.onBlur}
              />
            )}
          />
          <div className="max-w-xl mt-8">
            <Controller
              name="publishAs"
              control={control}
              render={({ field }) => (
                <Select
                  id="publish-as"
                  label="Publish your document as:"
                  options={postTypeOptions}
                  value={field.value}
                  onOptionSelect={(option) => field.onChange(option.value)}
                  onBlur={() => field.onBlur()}
                />
              )}
            />
          </div>

          <SectionMessage
            type="info"
            className="mt-4"
            message="Select a post type to publish your documents as. Choose 'Chosen by the author' to let document authors specify the post type via the 'wp-post-type' metadata field."
          />
        </div>
      </div>

      <div className="mt-10 pds-button-group">
        <Button
          label="Create collection"
          type="submit"
          disabled={!isValid || createCollectionFlow.isPending}
        />
        <Button
          label="Cancel"
          variant="subtle"
          onClick={onCancel}
          disabled={createCollectionFlow.isPending}
        />
      </div>
    </form>
  );
}
