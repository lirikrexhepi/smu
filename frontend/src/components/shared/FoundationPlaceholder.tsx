import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

type FoundationPlaceholderProps = {
  title: string
  description: string
}

export function FoundationPlaceholder({ title, description }: FoundationPlaceholderProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        <p className="text-sm text-slate-500">
          This route is intentionally a foundation placeholder. Final page UI and workflows will be
          built in later focused sessions from the mockups.
        </p>
      </CardContent>
    </Card>
  )
}
