import {
  ButtonLink,
  Card,
  CardHeading,
} from "@pantheon-systems/pds-toolkit-react";
import { Link } from "react-router-dom";

export default function Landing() {
  return (
    <div className="space-y-20">
      <div className="flex flex-col-reverse md:flex-row items-center gap-8">
        <div className="lg:basis-[60%] xl:basis-[54%]">
          <h1 className="pds-ts-4xl mb-8">
            Get started with Content Publisher by connecting a collection
          </h1>
          <p>
            Using Content Publisher starts with creating and connecting a
            collection for your content. Collections organize your documents for
            your different projects allowing you to define your content types
            and control what can get published where and by whom.
          </p>
        </div>
        <div>
          <img
            src={`${window.PCC_BOOTSTRAP.assets_url}/images/multi-icons.png`}
            alt="Dashboard"
            className="w-full min-w-[300px]"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <div slot="main">
            <CardHeading level="h2" text="Connect an existing collection" />
            <p>
              Connect a collection that you’ve previously created using the
              Content Publisher dashboard.
            </p>
            <ButtonLink
              linkContent={
                <Link to="/collections">Connect existing collection</Link>
              }
            />
          </div>
        </Card>
        <Card>
          <div slot="main">
            <CardHeading level="h2" text="Connect a new collection" />
            <p>
              Don’t have a collection yet? Create and connect a new one using
              the Content Publisher WordPress plugin.
            </p>
            <ButtonLink
              variant="secondary"
              linkContent={
                <Link to="/collections/create">Create new collection</Link>
              }
            />
          </div>
        </Card>
      </div>
    </div>
  );
}
