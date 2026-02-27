import { useMemo } from "react";

export function usePostTypeOptions() {
  return useMemo(() => {
    const availableTypes = window.CPUB_BOOTSTRAP.available_post_types;
    const options = availableTypes.map((pt) => ({
      label: pt.label,
      value: pt.name,
    }));
    options.push({
      label: "Chosen by the author (from document metadata)",
      value: "author_choice",
    });
    return options;
  }, []);
}
