import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import { HashRouter } from "react-router-dom";

import "./styles/index.css";
import "@pantheon-systems/pds-toolkit-react/css/pds-core.css";
import "@pantheon-systems/pds-toolkit-react/css/pds-components.css";

const container = document.getElementById("content-pub-root");
if (container) {
  const root = ReactDOM.createRoot(container);
  root.render(
    <React.StrictMode>
      <HashRouter>
        <App />
      </HashRouter>
    </React.StrictMode>
  );
}
