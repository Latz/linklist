{{- range . }}
{{- if or .Vulnerabilities .Misconfigurations .Secrets }}
{{ .Target }} ({{ .Type }})
{{- range .Vulnerabilities }}
  [{{ .Severity }}] {{ .PkgName }} {{ .VulnerabilityID }}: {{ .InstalledVersion }} -> {{ .FixedVersion }}
{{- end }}
{{- range .Misconfigurations }}
  [{{ .Severity }}] {{ .ID }} {{ .Title }}
{{- end }}
{{- range .Secrets }}
  [{{ .Severity }}] {{ .RuleID }} at line {{ .StartLine }}
{{- end }}
{{- end }}
{{- end }}
