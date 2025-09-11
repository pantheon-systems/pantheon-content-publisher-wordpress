import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import { HashRouter } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

import "./styles/index.css";
import "@pantheon-systems/pds-toolkit-react/css/pds-core.css";
import "@pantheon-systems/pds-toolkit-react/css/pds-components.css";
import { GlobalWrapper } from "@pantheon-systems/pds-toolkit-react";

const container = document.getElementById("content-pub-root");
if (container) {
  const root = ReactDOM.createRoot(container);
  const queryClient = new QueryClient();
  root.render(
    <React.StrictMode>
      <HashRouter>
        <QueryClientProvider client={queryClient}>
          <GlobalWrapper>
            <App />
          </GlobalWrapper>
        </QueryClientProvider>
      </HashRouter>
    </React.StrictMode>
  );
}
