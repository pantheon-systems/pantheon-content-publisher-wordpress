import { Routes, Route, Link } from "react-router-dom";
import Landing from "./views/Landing";
import Collections from "./views/collections";
import Dashboard from "./views/Dashboard";
import {
  Navbar,
  Container,
  NavMenu,
} from "@pantheon-systems/pds-toolkit-react";

export default function App() {
  const isPCCConfigured = window.PCC_BOOTSTRAP.is_pcc_configured;

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
