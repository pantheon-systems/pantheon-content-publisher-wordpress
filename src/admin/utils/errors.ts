import { isAxiosError } from "axios";
import type { AxiosError } from "axios";

export function getErrorMessage(
  error: Error | AxiosError | unknown | undefined,
  fallbackMessage: string
) {
  return !error
    ? null
    : isAxiosError(error)
      ? error.response?.data || error.message
      : error instanceof Error
        ? error.message
        : fallbackMessage;
}
