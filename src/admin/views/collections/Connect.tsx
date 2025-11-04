import {
  Breadcrumb,
  Button,
  ButtonLink,
  FlowSteps,
  LinkNewWindow,
  Spinner,
  TextInput,
  SectionMessage,
} from "@pantheon-systems/pds-toolkit-react";
import { Link, useNavigate } from "react-router-dom";
import { useState } from "react";
import { Controller, useForm } from "react-hook-form";
import { useMutation } from "@tanstack/react-query";
import CollectionReady from "../../components/collections/CollectionReady";
import { apiClient } from "../../api/client";
import { getErrorMessage } from "../../lib/errors";

export default function ConnectCollection() {
  const navigate = useNavigate();
  const [view, setView] = useState<"form" | "loading" | "ready">("form");

  const {
    control,
    handleSubmit,
    formState: { isValid },
  } = useForm<{
    collectionId: string;
    accessToken: string;
  }>({
    mode: "onChange",
    defaultValues: { collectionId: "", accessToken: "" },
  });

  const connectMutation = useMutation({
    mutationFn: async ({
      collectionId,
      accessToken,
    }: {
      collectionId: string;
      accessToken: string;
    }) => {
      return apiClient.post("/collection/connect", {
        collection_id: collectionId,
        access_token: accessToken,
      });
    },
    onMutate: () => {
      setView("loading");
    },
    onSuccess: () => {
      setView("ready");
    },
    onError: () => {
      setView("form");
    },
  });

  const onSubmit = (data: { collectionId: string; accessToken: string }) => {
    connectMutation.mutate(data);
  };

  const steps = [
    {
      header: "Enter your Collection ID",
      content: (
        <div>
          <p className="mb-3">
            Copy and paste the Collection ID for the collection you&apos;d like
            to connect.
          </p>
          <ButtonLink
            variant="secondary"
            className="mb-4"
            displayType="icon-end"
            iconName="externalLink"
            linkContent={
              <a
                href="https://content.pantheon.io/dashboard/collections"
                target="_blank"
                rel="noreferrer"
              >
                Go to your collections in Content Publisher
              </a>
            }
          />
          <Controller
            name="collectionId"
            control={control}
            rules={{ required: true }}
            render={({ field }) => (
              <TextInput
                id="collection-id"
                label="Collection ID"
                placeholder="12345678"
                className="mt-4"
                value={field.value}
                onChange={(e) => field.onChange(e.target.value)}
                onBlur={field.onBlur}
              />
            )}
          />
        </div>
      ),
    },
    {
      header: "Enter access token",
      content: (
        <div>
          <p className="mb-3">
            Generate an access token in the Content Publisher dashboard.
          </p>
          <ButtonLink
            variant="secondary"
            className="mb-4"
            displayType="icon-end"
            iconName="externalLink"
            linkContent={
              <a
                href="https://content.pantheon.io/dashboard/settings/tokens"
                target="_blank"
                rel="noreferrer"
              >
                Generate access token in Content Publisher
              </a>
            }
          />
          <Controller
            name="accessToken"
            control={control}
            rules={{ required: true }}
            render={({ field }) => (
              <TextInput
                id="access-token"
                label="Access token"
                placeholder="****************"
                className="mt-4"
                type="password"
                autoComplete="off"
                value={field.value}
                onChange={(e) => field.onChange(e.target.value)}
                onBlur={field.onBlur}
              />
            )}
          />
        </div>
      ),
    },
  ];

  return (
    <div>
      {view === "form" ? (
        <div>
          <Breadcrumb crumbs={[<Link to="/">Back</Link>]} />

          <div className="mt-8">
            <h2>Connect an existing collection to your WordPress site</h2>
            <p>
              This setup is for those who have already created a collection.{" "}
              <LinkNewWindow url="https://docs.content.pantheon.io">
                View documentation
              </LinkNewWindow>
            </p>
          </div>

          {connectMutation.error && (
            <SectionMessage
              type="critical"
              message={getErrorMessage(
                connectMutation.error,
                "Failed to connect collection. Please try again."
              )}
              className="mt-6"
            />
          )}

          <form onSubmit={handleSubmit(onSubmit)}>
            <div className="mt-8 flex gap-8 justify-between">
              <div className="w-full">
                <FlowSteps steps={steps} className="m-0" />
              </div>

              <div className="hidden lg:block shrink-0">
                <div className="flex flex-col gap-6 items-end">
                  <img
                    src={`${window.CPUB_BOOTSTRAP.assets_url}/images/copy-collection-id.png`}
                    alt="Copy collection ID"
                    className="max-h-[220px] object-contain"
                  />
                  <img
                    src={`${window.CPUB_BOOTSTRAP.assets_url}/images/create-access-token.png`}
                    alt="Create management token"
                    className="max-h-[220px] object-contain"
                  />
                </div>
              </div>
            </div>

            <div className="mt-10 pds-button-group">
              <Button
                type="submit"
                label="Continue"
                disabled={!isValid || connectMutation.isPending}
              />
              <Button
                label="Cancel"
                variant="subtle"
                onClick={() => navigate("/")}
                disabled={connectMutation.isPending}
              />
            </div>
          </form>
        </div>
      ) : view === "loading" ? (
        <div className="w-full h-[80vh] flex flex-col items-center justify-center">
          <Spinner label="Connecting your collection..." size="4xl" showLabel />
        </div>
      ) : (
        <CollectionReady />
      )}
    </div>
  );
}
