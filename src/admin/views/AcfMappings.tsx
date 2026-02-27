import { useEffect, useState } from "react";
import {
  useQuery,
  useMutation,
  useQueryClient,
} from "@tanstack/react-query";
import {
  Button,
  SectionMessage,
  useToast,
  ToastType,
} from "@pantheon-systems/pds-toolkit-react";
import { apiClient } from "../api/client";
import { getErrorMessage } from "../lib/errors";

interface AcfMapping {
  post_type: string;
  acf_field: string;
  cpub_field: string;
}

interface AcfField {
  key: string;
  label: string;
  name: string;
  type: string;
  group: string;
}

interface AcfMappingsResponse {
  acf_active: boolean;
  mappings: AcfMapping[];
  user_match_by: "login" | "email";
  errors: string[];
}

interface FieldRowProps {
  field: AcfField;
  cpubValue: string;
  onChange: (acfFieldName: string, cpubField: string) => void;
}

function FieldRow({ field, cpubValue, onChange }: FieldRowProps) {
  return (
    <tr className="border-b border-gray-200 last:border-0">
      <td className="py-2 px-3">
        <div className="text-sm font-medium text-gray-800">{field.label || field.name}</div>
        <div className="text-xs text-gray-400 font-mono mt-0.5">{field.key}</div>
      </td>
      <td className="py-2 px-3">
        <input
          type="text"
          value={cpubValue}
          onChange={(e) => onChange(field.name, e.target.value)}
          placeholder={`e.g. ${field.label || field.name}`}
          className="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
        />
      </td>
    </tr>
  );
}

interface FieldTableProps {
  postType: string;
  cpubValues: Record<string, string>;
  onChangeField: (acfName: string, cpubField: string) => void;
  onFieldsLoaded: (fields: AcfField[]) => void;
}

function FieldTable({ postType, cpubValues, onChangeField, onFieldsLoaded }: FieldTableProps) {
  const { data: fields = [], isLoading } = useQuery<AcfField[]>({
    queryKey: ["acf-fields", postType],
    queryFn: async () => {
      const res = await apiClient.get<{ fields: AcfField[] }>(
        `/acf-fields?post_type=${encodeURIComponent(postType)}`
      );
      return res.data.fields;
    },
    enabled: Boolean(postType),
  });

  // Notify parent of loaded fields for user-type detection
  useEffect(() => {
    if (fields.length > 0) {
      onFieldsLoaded(fields);
    }
  }, [fields, onFieldsLoaded]);

  if (isLoading) {
    return <p className="text-sm text-gray-500 py-2">Loading ACF fields…</p>;
  }

  if (fields.length === 0) {
    return (
      <p className="text-sm text-gray-500 py-2 italic">
        No ACF fields found for post type <code>{postType}</code>. Create an ACF
        field group assigned to this post type first.
      </p>
    );
  }

  return (
    <table className="w-full text-left border border-gray-200 rounded">
      <thead>
        <tr className="bg-gray-100">
          <th className="py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wide w-1/2">
            Advanced Custom Fields
          </th>
          <th className="py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wide">
            Content Publisher Metadata Field
          </th>
        </tr>
      </thead>
      <tbody>
        {fields.map((field) => (
          <FieldRow
            key={field.name}
            field={field}
            cpubValue={cpubValues[field.name] ?? ""}
            onChange={onChangeField}
          />
        ))}
      </tbody>
    </table>
  );
}

export default function AcfMappings() {
  const [addToast] = useToast();
  const queryClient = useQueryClient();

  const { data, isLoading, error } = useQuery<AcfMappingsResponse>({
    queryKey: ["acf-mappings"],
    queryFn: async () => {
      const res = await apiClient.get<AcfMappingsResponse>("/acf-mappings");
      return res.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (payload: { mappings: AcfMapping[]; user_match_by: "login" | "email" }) => {
      const res = await apiClient.put<AcfMappingsResponse>("/acf-mappings", payload);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["acf-mappings"] });
    },
  });
  const acfActive = window.CPUB_BOOTSTRAP.acf_active;

  const configuredPostType =
    (window.CPUB_BOOTSTRAP.configured.publish_as as string) ?? "post";

  // Local state: acf_field_name → cpub_field
  const [localMappings, setLocalMappings] = useState<Record<string, string>>(() => {
    const initial: Record<string, string> = {};
    if (data) {
      data.mappings
        .filter((m) => m.post_type === configuredPostType)
        .forEach((m) => {
          initial[m.acf_field] = m.cpub_field;
        });
    }
    return initial;
  });

  const [userMatchBy, setUserMatchBy] = useState<"login" | "email">(
    data?.user_match_by ?? "login"
  );

  // Track loaded ACF fields for user-type detection
  const [loadedFields, setLoadedFields] = useState<AcfField[]>([]);

  // Sync remote data into local state once loaded
  const [syncedFromServer, setSyncedFromServer] = useState(false);
  useEffect(() => {
    if (data && !syncedFromServer) {
      setSyncedFromServer(true);
      setUserMatchBy(data.user_match_by ?? "login");
      const synced: Record<string, string> = {};
      data.mappings
        .filter((m) => m.post_type === configuredPostType)
        .forEach((m) => {
          synced[m.acf_field] = m.cpub_field;
        });
      setLocalMappings((prev) => ({ ...prev, ...synced }));
    }
  }, [data, syncedFromServer, configuredPostType]);

  const handleFieldChange = (acfName: string, cpubField: string) => {
    setLocalMappings((prev) => ({ ...prev, [acfName]: cpubField }));
  };

  const handleSave = () => {
    // Build flat mappings array, omitting empty cpub fields
    const mappings: AcfMapping[] = Object.entries(localMappings)
      .filter(([, cpubField]) => cpubField.trim())
      .map(([acfField, cpubField]) => ({
        post_type: configuredPostType,
        acf_field: acfField,
        cpub_field: cpubField.trim(),
      }));

    saveMutation.mutate(
      { mappings, user_match_by: userMatchBy },
      {
        onSuccess: () => {
          addToast(ToastType.Success, "Metadata mapping saved.");
        },
        onError: (err) => {
          addToast(
            ToastType.Critical,
            getErrorMessage(err, "Failed to save mappings.") ?? "Failed to save mappings."
          );
        },
      }
    );
  };

  if (isLoading) {
    return <p className="text-sm text-gray-500">Loading…</p>;
  }

  if (error) {
    return (
      <SectionMessage
        type="critical"
        message={getErrorMessage(error, "Failed to load ACF mappings.")}
      />
    );
  }

  // ACF not active: show install prompt
  if (!acfActive) {
    return (
      <div className="space-y-6">
        <div>
          <h3 className="pds-ts-xl font-semibold mb-1">Advanced Custom Fields</h3>
          <p className="text-sm text-gray-600">
            Metadata fields defined in the Pantheon Content Publisher collection
            can be synced to Advanced Custom Fields.
          </p>
        </div>
        <SectionMessage
          type="warning"
          message="ACF is not installed or activated. Install Advanced Custom Fields to use this feature."
        />
      </div>
    );
  }

  const hasUserFields = loadedFields.some((f) => f.type === "user");

  return (
    <div className="space-y-6">
      <div>
        <h3 className="pds-ts-xl font-semibold mb-1">Advanced Custom Fields</h3>
        <p className="text-sm text-gray-600">
          Metadata fields defined in the Pantheon Content Publisher collection
          can be synced to Advanced Custom Fields.
        </p>
      </div>

      {/* Recent sync errors from server */}
      {data?.errors && data.errors.length > 0 && (
        <SectionMessage
          type="critical"
          message={
            "Errors from last sync:\n" +
            data.errors.map((e) => `• ${e}`).join("\n")
          }
        />
      )}

      {/* Field mapping table */}
      <FieldTable
        postType={configuredPostType}
        cpubValues={localMappings}
        onChangeField={handleFieldChange}
        onFieldsLoaded={setLoadedFields}
      />

      {/* User field matching — only when user-type ACF fields exist */}
      {hasUserFields && (
        <div className="space-y-2">
          <p className="text-sm font-medium text-gray-700">
            Map ACF user fields by:
          </p>
          <div className="flex gap-6">
            <label className="flex items-center gap-2 text-sm cursor-pointer">
              <input
                type="radio"
                name="userMatchBy"
                value="login"
                checked={userMatchBy === "login"}
                onChange={() => setUserMatchBy("login")}
                className="accent-purple-600"
              />
              User login
            </label>
            <label className="flex items-center gap-2 text-sm cursor-pointer">
              <input
                type="radio"
                name="userMatchBy"
                value="email"
                checked={userMatchBy === "email"}
                onChange={() => setUserMatchBy("email")}
                className="accent-purple-600"
              />
              User email
            </label>
          </div>
        </div>
      )}

      <div className="pds-button-group">
        <Button
          label="Save Mapping"
          type="button"
          onClick={handleSave}
          isLoading={saveMutation.isPending}
          disabled={saveMutation.isPending}
        />
      </div>

      <p className="text-xs text-gray-400">
        Enter the metadata field name exactly as defined in your PCC collection.
        Leave blank to skip that field. Field names are case-sensitive. Mappings
        are applied on every publish; renaming a field requires updating the
        mapping manually.
      </p>
    </div>
  );
}
