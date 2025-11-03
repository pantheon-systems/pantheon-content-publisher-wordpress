import { Routes, Route } from "react-router-dom";
import ConnectCollection from "./Connect";
import CreateCollection from "./Create";

export default function Collections() {
  return (
    <Routes>
      <Route path="" element={<ConnectCollection />} />
      <Route path="create" element={<CreateCollection />} />
    </Routes>
  );
}
