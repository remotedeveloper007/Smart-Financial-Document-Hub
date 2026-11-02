import React from 'react';
import { render, screen } from '@testing-library/react';
import { test, expect } from 'vitest';
import ImportCsvChunkedUploader from '../ImportCsvChunkedUploader';

test('renders uploader and shows drag & drop hint', () => {
  render(<ImportCsvChunkedUploader integrationId={1} />);
  expect(screen.getByText(/Or drag & drop CSV here/i)).toBeTruthy();
  expect(screen.getByText(/Upload \(chunked\)/i)).toBeTruthy();
});
