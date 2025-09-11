import {
  Breadcrumb,
  LinkNewWindow,
  Spinner,
} from "@pantheon-systems/pds-toolkit-react";
import StepCreateCollection from "./StepCreateCollection";
import { Link, useNavigate } from "react-router-dom";
import StepConnectToken from "./StepConnectToken";
import { useState } from "react";
import CollectionReady from "../../../components/collections/CollectionReady";

const steps = [
  {
    id: 1,
    title: "First, connect Content Publisher to your WordPress site",
  },
  {
    id: 2,
    title: "Create your first collection",
  },
];

export default function CreateCollection() {
  const [step, setStep] = useState(steps[0]);
  const [isReady, setIsReady] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [loadingStep, setLoadingStep] = useState<string>(
    "Creating your collection..."
  );
  const navigate = useNavigate();

  if (isReady) {
    return <CollectionReady />;
  }

  if (isLoading) {
    return (
      <div className="w-full h-[80vh] flex flex-col items-center justify-center">
        <Spinner label={loadingStep} size="4xl" showLabel />
      </div>
    );
  }

  return (
    <div>
      <Breadcrumb crumbs={[<Link to="/">Back</Link>]} />
      <div className="mt-8">
        <div>
          <p className="pds-ts-s uppercase text-pds-color-text-default-secondary font-bold text-second">
            Step {step.id} of {steps.length}
          </p>
          <h2 className="mt-2">{step.title}</h2>
          {step.id === 1 && (
            <p>
              This setup is for those who don&apos;t have a collection yet.{" "}
              <LinkNewWindow url="https://docs.content.pantheon.io">
                View documentation
              </LinkNewWindow>
            </p>
          )}
        </div>

        {step.id === 1 ? (
          <StepConnectToken
            onNext={() => setStep(steps[1])}
            onCancel={() => navigate("/")}
          />
        ) : (
          <StepCreateCollection
            onDone={() => setIsReady(true)}
            onCancel={() => navigate("/")}
            onLoadingChange={setIsLoading}
            onLoadingStepChange={setLoadingStep}
          />
        )}
      </div>
    </div>
  );
}
