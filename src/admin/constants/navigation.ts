export const SRC_ACTIONS = {
  DISCONNECTED: "disconnected",
} as const;

export type SrcAction = (typeof SRC_ACTIONS)[keyof typeof SRC_ACTIONS];
