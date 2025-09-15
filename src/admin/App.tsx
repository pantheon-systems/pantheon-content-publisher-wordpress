import { Routes, Route, Link } from "react-router-dom";
import Landing from "./views/Landing";
import Collections from "./views/collections";
import Dashboard from "./views/Dashboard";
import {
  Navbar,
  Container,
  NavMenu,
  useToast,
  ToastType,
} from "@pantheon-systems/pds-toolkit-react";
import { useEffect } from "react";
import { SRC_ACTIONS, SrcAction } from "./constants/navigation";

export default function App() {
  const isPCCConfigured = window.PCC_BOOTSTRAP.is_pcc_configured;
  const [addToast] = useToast();

  // Handle source actions
  useEffect(() => {
    const url = new URL(window.location.href);
    const src = url.searchParams.get("src") as SrcAction | null;

    switch (src) {
      case SRC_ACTIONS.DISCONNECTED:
        addToast(ToastType.Success, "Collection disconnected successfully");
        break;
      default:
        break;
    }

    if (src) {
      url.searchParams.delete("src");
      window.history.replaceState({}, "", url.toString());
    }
  }, [addToast]);

  return (
    <div className="w-full">
      <Navbar
        logoDisplayType="sub-brand"
        logoSubBrand="Content Publisher"
        logoLinkContent={<Link to="/">Pantheon</Link>}
        containerWidth="full"
      >
        <NavMenu
          ariaLabel="Main navigation"
          slot="items-right"
          menuItems={[
            {
              label: "Docs",
              linkContent: (
                <a
                  href="https://docs.content.pantheon.io"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Docs
                </a>
              ),
            },
          ]}
        />
      </Navbar>
      <Container width="x-wide" className="pt-8">
        <main>
          <Routes>
            <Route
              path="/"
              element={isPCCConfigured ? <Dashboard /> : <Landing />}
            />
            <Route path="/collections/*" element={<Collections />} />
          </Routes>
        </main>
      </Container>
    </div>
  );
}
