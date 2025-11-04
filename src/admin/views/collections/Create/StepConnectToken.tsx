import {
  Button,
  ButtonLink,
  SectionMessage,
  TextInput,
} from "@pantheon-systems/pds-toolkit-react";
import { useMutation } from "@tanstack/react-query";
import { Controller, useForm } from "react-hook-form";
import { apiClient } from "../../../api/client";
import { getErrorMessage } from "../../../lib/errors";

interface Props {
  onNext: () => void;
  onCancel: () => void;
}

export default function StepConnectToken({ onNext, onCancel }: Props) {
  const {
    control,
    handleSubmit,
    formState: { isValid },
  } = useForm<{
    managementToken: string;
  }>({
    mode: "onChange",
    defaultValues: { managementToken: "" },
  });

  const saveToken = useMutation({
    mutationFn: async (token: string) => {
      await apiClient.post("/oauth/access-token", { access_token: token });
    },
    onSuccess: onNext,
  });

  const onSubmit = (data: { managementToken: string }) => {
    if (saveToken.isPending) return;
    saveToken.mutate(data.managementToken);
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      {saveToken.error && (
        <SectionMessage
          type="critical"
          message={getErrorMessage(
            saveToken.error,
            "Failed to save management token. Please try again."
          )}
          className="mb-4"
        />
      )}

      <div className="mt-8 flex gap-8 justify-between">
        <div className="w-full">
          <h5>Enter management token</h5>
          <p>Generate a management token in the Content Publisher dashboard.</p>
          <ButtonLink
            variant="secondary"
            className="mr-3"
            displayType="icon-end"
            iconName="externalLink"
            linkContent={
              <a
                href="https://content.pantheon.io/dashboard/settings/tokens?tab=1"
                target="_blank"
              >
                Generate management token in Content Publisher
              </a>
            }
          />

          <Controller
            name="managementToken"
            control={control}
            rules={{ required: true }}
            render={({ field }) => (
              <TextInput
                id="management-token"
                label="Management token"
                className="mt-4"
                required
                type="password"
                placeholder="Enter management token"
                value={field.value}
                onChange={(e) => field.onChange(e.target.value)}
                onBlur={field.onBlur}
              />
            )}
          />
        </div>

        <img
          src={`${window.CPUB_BOOTSTRAP.assets_url}/images/create-management-token.png`}
          alt="Connect Content Publisher"
          className="max-h-[260px] sobject-contain hidden lg:block"
        />
      </div>

      <div className="mt-10 pds-button-group">
        <Button
          label="Connect"
          isLoading={saveToken.isPending}
          type="submit"
          disabled={!isValid || saveToken.isPending}
        />
        <Button label="Cancel" variant="subtle" onClick={onCancel} />
      </div>
    </form>
  );
}
