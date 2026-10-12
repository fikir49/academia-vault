import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Academia Vault',
  description: 'Bilingual Academic Information Retrieval System',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body style={{ margin: 0, padding: 0, backgroundColor: '#0f172a' }}>
        {children}
      </body>
    </html>
  );
}